<?php
/** Test the real backup CSV encoder without accessing a database. */
class WP_CLI {
    public static function error($message) { throw new RuntimeException($message); }
}
$source=file_get_contents(dirname(__DIR__).'/deploy/34a-keyword-map.php');
$start=strpos($source,'function pk34a_csv_write(');
$end=strpos($source,'// ---------- 读词表 ----------',$start);
if ($start===false || $end===false) throw new RuntimeException('Cannot locate backup encoder.');
eval(substr($source,$start,$end-$start));
$rows=array(
    array('version','ts','post_id','url','field','old_value','new_value','old_exists','old_is_null'),
    array('2','stamp','8','/products/','_packrivo_description',"line 1\r\nline 2, \"quoted\"",'new','1','0'),
    array('2','stamp','8','/products/','idx:42:description','','new','1','1'),
    array('2','stamp','8','/products/','_yoast_wpseo_title','','new','0','0'),
);
$csv=pk34a_csv_write($rows);$stream=fopen('php://temp','w+');fwrite($stream,$csv);rewind($stream);$actual=array();
while (($row=fgetcsv($stream,0,',','"',''))!==false) $actual[]=$row;
if ($actual!==$rows) throw new RuntimeException('Backup CSV round-trip changed source bytes.');
echo 'BACKUP_CSV_ROUNDTRIP rows='.count($actual)." PASS\n";
