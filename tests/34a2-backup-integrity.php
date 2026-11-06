<?php
require dirname(__DIR__).'/deploy/34a-backup-lib.php';
$rows=array(
    array('id'=>1,'url'=>'/a/','field'=>'h1','old'=>"old\r\nline",'new'=>'new','old_exists'=>true,'old_null'=>false),
    array('id'=>1,'url'=>'/a/','field'=>'idx:4:description','old'=>'','new'=>'desc','old_exists'=>true,'old_null'=>true),
    array('id'=>2,'url'=>'/b/','field'=>'_yoast_wpseo_title','old'=>'','new'=>'title','old_exists'=>false,'old_null'=>false),
    array('id'=>3,'url'=>'/c/','field'=>'_yoast_wpseo_title','old'=>'','new'=>'title','old_exists'=>true,'old_null'=>false),
);
$encoded=pk34a_backup_encode($rows,'stamp');
$decoded=pk34a_backup_decode($encoded);
if (count($decoded)!==4 || $decoded[0]['old_value']!=="old\r\nline" || $decoded[1]['old_is_null']!=='1' || $decoded[2]['old_exists']!=='0' || $decoded[3]['old_exists']!=='1') throw new RuntimeException('Valid backup did not round-trip.');
$stream=fopen('php://temp','w+');fwrite($stream,$encoded);rewind($stream);$parsed=array();
while (($row=fgetcsv($stream,0,',','"',''))!==false) $parsed[]=$row;
fclose($stream);
array_splice($parsed,1,1); // Remove one complete record while preserving footer.
$stream=fopen('php://temp','w+');foreach($parsed as $row) fputcsv($stream,$row,',','"','');rewind($stream);$truncated=stream_get_contents($stream);fclose($stream);
try { pk34a_backup_decode($truncated); throw new RuntimeException('Truncated backup was accepted.'); }
catch (UnexpectedValueException $expected) {}
$tampered=str_replace(',new,',',tampered,',$encoded);
try { pk34a_backup_decode($tampered); throw new RuntimeException('Tampered backup was accepted.'); }
catch (UnexpectedValueException $expected) {}
echo "BACKUP_INTEGRITY_CASES=3 PASS\n";
