<?php
/** Version-3 backup CSV: exact values plus a count and content checksum. */
function pk34a_backup_header() {
    return array('version','ts','post_id','url','field','old_value','new_value','old_exists','old_is_null','record_count','records_sha256');
}

function pk34a_backup_encode($records, $stamp) {
    if (!is_array($records) || !$records || !is_string($stamp) || $stamp==='') throw new UnexpectedValueException('Empty backup plan.');
    $data=array();
    foreach ($records as $r) {
        $data[]=array('3',$stamp,(string)$r['id'],(string)$r['url'],(string)$r['field'],(string)$r['old'],(string)$r['new'],$r['old_exists']?'1':'0',$r['old_null']?'1':'0');
    }
    $json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $end=array('3',$stamp,'0','','__END__','','','','',(string)count($data),hash('sha256',$json));
    $stream=fopen('php://temp','w+');
    if (!$stream) throw new UnexpectedValueException('Cannot create backup buffer.');
    foreach (array_merge(array(pk34a_backup_header()),$data,array($end)) as $row) {
        if (fputcsv($stream,$row,',','"','')===false) throw new UnexpectedValueException('Cannot encode backup.');
    }
    rewind($stream);$result=stream_get_contents($stream);fclose($stream);
    return $result;
}

function pk34a_backup_decode($payload) {
    if (!is_string($payload) || $payload==='') throw new UnexpectedValueException('Backup is empty.');
    $stream=fopen('php://temp','w+');fwrite($stream,$payload);rewind($stream);
    $header=fgetcsv($stream,0,',','"','');
    if (is_array($header)) $header[0]=preg_replace('/^\xEF\xBB\xBF/','',(string)$header[0]);
    if ($header!==pk34a_backup_header()) throw new UnexpectedValueException('Invalid or legacy backup format.');
    $data=array();$end=null;
    while (($row=fgetcsv($stream,0,',','"',''))!==false) {
        if ($end!==null) throw new UnexpectedValueException('Data follows backup footer.');
        if (count($row)===11 && $row[4]==='__END__') { $end=$row;continue; }
        if (count($row)!==9) throw new UnexpectedValueException('Invalid backup data row.');
        $data[]=$row;
    }
    fclose($stream);
    if (!$data || !$end || $end[0]!=='3' || $end[2]!=='0' || !ctype_digit($end[9]) || (int)$end[9]!==count($data) || !preg_match('/^[a-f0-9]{64}$/',$end[10])) throw new UnexpectedValueException('Backup footer or record count mismatch.');
    foreach ($data as $row) if ($row[0]!=='3' || $row[1]!==$end[1]) throw new UnexpectedValueException('Backup version or timestamp mismatch.');
    $json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    if (!hash_equals($end[10],hash('sha256',$json))) throw new UnexpectedValueException('Backup content checksum mismatch.');
    $keys=array_slice(pk34a_backup_header(),0,9);
    return array_map(static function($row) use ($keys){return array_combine($keys,$row);},$data);
}
