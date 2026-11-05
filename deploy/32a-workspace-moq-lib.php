<?php
/** Exact JSON-token editing for the product workspace snapshots. */
function pkpw32a_token($json) {
    if (!is_string($json)) throw new RuntimeException('record_json is not text');
    $data=json_decode($json,true);
    if (json_last_error()!==JSON_ERROR_NONE || !is_array($data) || !isset($data['commercial']) || !is_array($data['commercial']) ||
        !isset($data['commercial']['moq']) || !is_int($data['commercial']['moq']) || !in_array($data['commercial']['moq'],[250,500],true)) {
        throw new RuntimeException('invalid commercial.moq');
    }
    $length=strlen($json);$stack=[];$target_open=-1;$inside=false;$commercial_count=0;$found=[];
    for($i=0;$i<$length;){
        $c=$json[$i];
        if($c==='"'){
            $start=$i++;$escaped=false;
            while($i<$length){$ch=$json[$i++];if($escaped){$escaped=false;continue;}if($ch==='\\'){$escaped=true;continue;}if($ch==='"')break;}
            $key=json_decode(substr($json,$start,$i-$start),true);
            $j=$i;while($j<$length && ctype_space($json[$j]))$j++;
            if($j<$length && $json[$j]===':'){
                $j++;while($j<$length && ctype_space($json[$j]))$j++;
                if(count($stack)===1 && $stack[0]==='{' && $key==='commercial'){
                    $commercial_count++;if($j >= $length || $json[$j]!=='{')throw new RuntimeException('commercial is not object');$target_open=$j;
                }
                if($inside && count($stack)===2 && $key==='moq'){
                    if(!preg_match('/\A(?:500|250)(?=\s*[,}])/',substr($json,$j),$m))throw new RuntimeException('invalid MOQ token');
                    $found[]=['offset'=>$j,'token'=>$m[0]];
                }
            }
            continue;
        }
        if($c==='{' || $c==='['){if($i===$target_open)$inside=true;$stack[]=$c;}
        elseif($c==='}' || $c===']'){if($inside && count($stack)===2)$inside=false;array_pop($stack);}
        $i++;
    }
    if($commercial_count!==1 || count($found)!==1 || (int)$found[0]['token']!==$data['commercial']['moq'])throw new RuntimeException('ambiguous MOQ token');
    return ['offset'=>$found[0]['offset'],'moq'=>$data['commercial']['moq']];
}
function pkpw32a_stable_hash($json) {
    $v=pkpw32a_token($json);return hash('sha256',substr_replace($json,'<MOQ>',$v['offset'],3));
}
function pkpw32a_swap($json,$from,$to) {
    $v=pkpw32a_token($json);
    if($v['moq']!==$from || !in_array($to,[250,500],true))throw new RuntimeException('unexpected MOQ transition');
    $out=substr_replace($json,(string)$to,$v['offset'],3);
    if(pkpw32a_token($out)['moq']!==$to || pkpw32a_stable_hash($json)!==pkpw32a_stable_hash($out))throw new RuntimeException('non-MOQ bytes changed');
    return $out;
}
function pkpw32a_rows($lock=false) {
    global $wpdb;
    $table=$wpdb->prefix.'pkpw_snapshots';
    $engine=$wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));
    if(strtoupper((string)$engine)!=='INNODB')throw new RuntimeException('snapshot table missing or not InnoDB');
    $rows=$wpdb->get_results("SELECT revision,page_id,lane,record_json FROM {$table} ORDER BY revision".($lock?' FOR UPDATE':''),ARRAY_A);
    if(!is_array($rows) || !$rows || $wpdb->last_error)throw new RuntimeException('no readable snapshots');
    return $rows;
}
function pkpw32a_csv_rows($path) {
    $f=@fopen($path,'r');if(!$f)throw new RuntimeException('backup CSV unavailable');
    $expected=['revision','page_id','lane','old_moq','before_sha256','after_sha256','stable_sha256'];
    if(fgetcsv($f)!==$expected)throw new RuntimeException('unexpected backup CSV header');
    $out=[];
    while(($row=fgetcsv($f))!==false){
        if(count($row)!==count($expected))throw new RuntimeException('invalid backup CSV row');
        $x=array_combine($expected,$row);
        if(!ctype_digit($x['revision'])||!ctype_digit($x['page_id'])||!in_array($x['old_moq'],['250','500'],true)||isset($out[$x['revision']]))throw new RuntimeException('invalid/duplicate backup row');
        foreach(['before_sha256','after_sha256','stable_sha256'] as $k)if(!preg_match('/\A[a-f0-9]{64}\z/',$x[$k]))throw new RuntimeException('invalid backup hash');
        $out[$x['revision']]=$x;
    }
    fclose($f);if(!$out)throw new RuntimeException('empty backup CSV');return $out;
}
function pkpw32a_write_backup() {
    $rows=pkpw32a_rows();$f=fopen('php://output','w');
    fputcsv($f,['revision','page_id','lane','old_moq','before_sha256','after_sha256','stable_sha256']);
    foreach($rows as $row){
        $json=$row['record_json'];$old=pkpw32a_token($json)['moq'];$after=$old===500?pkpw32a_swap($json,500,250):$json;
        fputcsv($f,[$row['revision'],$row['page_id'],$row['lane'],$old,hash('sha256',$json),hash('sha256',$after),pkpw32a_stable_hash($json)]);
    }
    fclose($f);
}
function pkpw32a_apply_backup($path,$mode) {
    global $wpdb;
    if(!in_array($mode,['migrate','rollback'],true))throw new RuntimeException('invalid operation');
    $backup=pkpw32a_csv_rows($path);$table=$wpdb->prefix.'pkpw_snapshots';
    if($wpdb->query('START TRANSACTION')===false)throw new RuntimeException('transaction start failed');
    $commit_attempted=false;
    try {
        $rows=pkpw32a_rows(true);$seen=[];$pending=[];$before=[250=>0,500=>0];$after=$before;
        foreach($rows as $row){
            $id=(string)$row['revision'];$seen[$id]=true;$b=$backup[$id]??null;
            if(!$b || (string)$row['page_id']!==$b['page_id'] || $row['lane']!==$b['lane'])throw new RuntimeException('snapshot inventory changed');
            $json=$row['record_json'];$moq=pkpw32a_token($json)['moq'];$before[$moq]++;
            if(!hash_equals($b['stable_sha256'],pkpw32a_stable_hash($json)))throw new RuntimeException('non-MOQ data differs from backup');
            $old=(int)$b['old_moq'];$target=$mode==='migrate'?250:$old;
            $valid_hash=$moq===$old?$b['before_sha256']:($old===500 && $moq===250?$b['after_sha256']:'');
            if($valid_hash==='' || !hash_equals($valid_hash,hash('sha256',$json)))throw new RuntimeException('unexpected snapshot state');
            $result=$moq===$target?$json:pkpw32a_swap($json,$moq,$target);
            $wanted=$target===$old?$b['before_sha256']:$b['after_sha256'];
            if(!hash_equals($wanted,hash('sha256',$result)))throw new RuntimeException('candidate hash mismatch');
            $after[$target]++;
            if($result!==$json)$pending[]=['row'=>$row,'new'=>$result];
        }
        if(count($seen)!==count($backup))throw new RuntimeException('snapshot inventory differs from backup');
        WP_CLI::line('before_500='.$before[500].' before_250='.$before[250]);
        foreach($pending as $x){
            $row=$x['row'];$updated=$wpdb->update($table,['record_json'=>$x['new']],['revision'=>$row['revision'],'page_id'=>$row['page_id'],'record_json'=>$row['record_json']]);
            if($updated!==1)throw new RuntimeException('concurrent change or database error');
        }
        $commit_attempted=true;
        if($wpdb->query('COMMIT')===false)throw new RuntimeException('commit outcome unknown; inspect the snapshots and backup');
        WP_CLI::line('after_500='.$after[500].' after_250='.$after[250]);
        WP_CLI::line('replaced='.count($pending).' mode='.$mode.' snapshots='.count($rows));
    } catch (Throwable $e) {
        if(!$commit_attempted && $wpdb->query('ROLLBACK')===false)throw new RuntimeException($e->getMessage().'; rollback could not be confirmed',0,$e);
        throw $e;
    }
}
