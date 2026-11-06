<?php
require dirname(__DIR__).'/deploy/34a-plan-lib.php';
$acts=array(7=>array('title'=>array('after'=>'New title'),'meta'=>array('after'=>'New meta'),'h1'=>array('after'=>'New heading')));
$theme=array('title'=>'Quote title','desc'=>'Quote description','h1'=>'Quote heading');
$manifest=array(
    array('source'=>'yoast_title','new_en'=>'New title'),
    array('source'=>'yoast_meta','new_en'=>'New meta'),
    array('source'=>'h1','new_en'=>'New heading'),
    array('source'=>'theme','url'=>'/get-a-quote/','new_en'=>'Quote title'),
    array('source'=>'theme','url'=>'/get-a-quote/','new_en'=>'Quote description'),
    array('source'=>'theme','url'=>'/get-a-quote/','new_en'=>'Quote heading'),
);
if (pk34a_manifest_gaps($acts,$manifest,$theme)) throw new RuntimeException('Complete manifest rejected.');
$missing=$manifest;array_splice($missing,1,1);
if (count(pk34a_manifest_gaps($acts,$missing,$theme))!==1) throw new RuntimeException('Missing planned meta was not rejected.');
$wrong=$manifest;$wrong[0]['source']='h1';
if (count(pk34a_manifest_gaps($acts,$wrong,$theme))!==1) throw new RuntimeException('Wrong bucket group was accepted.');
$missing=$manifest;array_pop($missing);
if (count(pk34a_manifest_gaps($acts,$missing,$theme))!==1) throw new RuntimeException('Missing quote theme string was not rejected.');
$raw="source,url,old_en,new_en\n";
if (!pk34a_manifest_hash_matches($raw,hash('sha256',$raw))) throw new RuntimeException('Matching manifest checksum rejected.');
if (pk34a_manifest_hash_matches($raw,str_repeat('0',64))) throw new RuntimeException('Changed manifest checksum accepted.');
echo "PLAN_MANIFEST_CASES=6 PASS\n";
