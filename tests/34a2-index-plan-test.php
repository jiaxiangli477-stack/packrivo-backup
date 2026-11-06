<?php
$source=file_get_contents(dirname(__DIR__).'/deploy/34a-keyword-map.php');
$start=strpos($source,'function pk34a_norm(');$end=strpos($source,'function pk34a_path(',$start);
if ($start===false || $end===false) throw new RuntimeException('Cannot locate planning helpers.');
eval(substr($source,$start,$end-$start));
$page=array('cur_title'=>'New title','new_title'=>'New title','cur_meta'=>'New description','new_meta'=>'New description');
$rows=array(array('id'=>'7','title'=>'Old cache title','description'=>'New description'));
$result=pk34a_index_plan($page,array(),$rows);
if (count($result)!==1 || $result[0]['column']!=='title' || $result[0]['new']!=='New title') throw new RuntimeException('Stale indexable not planned.');
$page['cur_title']='Unexpected title';
if (pk34a_index_plan($page,array(),$rows)) throw new RuntimeException('Conflicting title must not be forced.');
$rows[0]['title']='New title';
$page['cur_title']='New title';
if (pk34a_index_plan($page,array(),$rows)) throw new RuntimeException('Current indexable should be a no-op.');
echo "INDEXABLE_PLAN_CASES=3 PASS\n";
