<?php
/** Bind actual planned English changes to the CSV used by the translation gate. */
function pk34a_manifest_hash_matches($payload, $expected) {
    return is_string($payload) && is_string($expected) && preg_match('/^[a-f0-9]{64}$/',$expected) === 1
        && hash_equals($expected,hash('sha256',$payload));
}
function pk34a_manifest_gaps($acts, $manifest, $theme) {
    $groups=array(
        'title'=>array('yoast_title','hub_title'), 'hub_title'=>array('yoast_title','hub_title'),
        'meta'=>array('yoast_meta','hub_desc'), 'hub_desc'=>array('yoast_meta','hub_desc'),
        'h1'=>array('h1'),
    );
    $keys=array();
    foreach ($manifest as $row) {
        if (!isset($row['source'],$row['new_en']) || !is_string($row['new_en']) || $row['new_en']==='') continue;
        if ($row['source']==='theme' && ($row['url']??'')!=='/get-a-quote/') continue;
        $value=str_replace(array("\r\n","\r"),"\n",$row['new_en']);
        $keys[$row['source']."\x1f".$value]=true;
    }
    $gaps=array();
    foreach ($acts as $id=>$fields) foreach ($fields as $slot=>$entry) {
        if (!isset($groups[$slot],$entry['after'])) throw new UnexpectedValueException('Unexpected planned field.');
        $value=str_replace(array("\r\n","\r"),"\n",(string)$entry['after']);$found=false;
        foreach ($groups[$slot] as $source) if (isset($keys[$source."\x1f".$value])) { $found=true;break; }
        if (!$found) $gaps[]=array('id'=>(int)$id,'field'=>$slot,'value'=>$value);
    }
    foreach (array('title','desc','h1') as $slot) {
        if (!isset($theme[$slot]) || !is_string($theme[$slot]) || $theme[$slot]==='') throw new UnexpectedValueException('Quote theme string missing.');
        $value=str_replace(array("\r\n","\r"),"\n",$theme[$slot]);
        if (!isset($keys['theme'."\x1f".$value])) $gaps[]=array('id'=>0,'field'=>'theme_'.$slot,'value'=>$value);
    }
    return $gaps;
}
