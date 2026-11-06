<?php
/** R1 read-only local HTML redline audit. Input is JSON array of paths. */
function r1_has_500($value){return is_string($value)&&preg_match('/500/u',$value)===1;}
function r1_is_allowed_1094($path){return $path==='/2026/10/10/fsc-packaging-label-permission/';}
if(defined('R1_REDLINE_TEST_ONLY')&&R1_REDLINE_TEST_ONLY)return;
if(PHP_SAPI!=='cli'||$argc!==3){fwrite(STDERR,"usage: php r1-redline-scan.php paths.json new-results.jsonl\n");exit(2);}
$paths=json_decode((string)@file_get_contents($argv[1]),true);if(!is_array($paths)||!$paths||file_exists($argv[2])){fwrite(STDERR,"bad input or output exists\n");exit(2);}
foreach($paths as $p)if(!is_string($p)||!preg_match('~^/(?!/)[^\r\n]*$~',$p)||strpos($p,'://')!==false){fwrite(STDERR,"unsafe path\n");exit(2);}
$out=fopen($argv[2],'x');if(!$out)exit(2);$queue=array_values(array_unique($paths));$mh=curl_multi_init();$active=[];$next=0;$done=0;$bad=0;$heads=0;$bodycount=0;
while($next<count($queue)||$active){
 while($next<count($queue)&&count($active)<6){$path=$queue[$next++];$c=curl_init('http://packrivo-r1-proxy'.$path);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>90,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_PROXY=>'',CURLOPT_HTTPHEADER=>['Host: www.packrivo.com'],CURLOPT_USERAGENT=>'r1-4-redline-local']);$active[spl_object_id($c)]=[$c,$path];curl_multi_add_handle($mh,$c);}
 do{$rc=curl_multi_exec($mh,$running);}while($rc===CURLM_CALL_MULTI_PERFORM);if($rc!==CURLM_OK){fwrite(STDERR,"curl multi failure\n");exit(2);}
 while($info=curl_multi_info_read($mh)){$c=$info['handle'];$id=spl_object_id($c);[$ignore,$path]=$active[$id];$body=curl_multi_getcontent($c);$code=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);$rec=['path'=>$path,'status'=>$code,'whitelist_1094'=>r1_is_allowed_1094(parse_url($path,PHP_URL_PATH)?:'/'),'fields'=>[],'body'=>[],'noindex'=>false];if($code!==200)$bad++;
 if($code===200&&is_string($body)){$d=new DOMDocument();libxml_use_internal_errors(true);$d->loadHTML('<?xml encoding="UTF-8" ?>'.$body,LIBXML_NONET);libxml_clear_errors();$xp=new DOMXPath($d);
  $queries=['title'=>'//title','meta_description'=>'//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]','og_title'=>'//meta[translate(@property,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="og:title" or translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="og:title"]','og_description'=>'//meta[translate(@property,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="og:description" or translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="og:description"]','twitter_description'=>'//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="twitter:description" or translate(@property,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="twitter:description"]','h1'=>'//h1'];
  foreach($queries as $kind=>$query){$nodes=$xp->query($query);if(!$nodes)continue;foreach($nodes as $node){$value=$node->nodeName==='meta'?$node->getAttribute('content'):$node->textContent;if(r1_has_500($value)){$rec['fields'][]=['field'=>$kind,'value'=>trim(preg_replace('/\s+/u',' ',$value))];$heads++;}}}
  $robots=$xp->query('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="robots"]');if($robots)foreach($robots as $n)if(preg_match('/\bnoindex\b/i',$n->getAttribute('content')))$rec['noindex']=true;
  $root=$xp->query('//main')->item(0);if(!$root)$root=$xp->query('//body')->item(0);if($root){foreach(iterator_to_array($xp->query('.//script | .//style | .//noscript | .//template | .//svg',$root)) as $n)if($n->parentNode)$n->parentNode->removeChild($n);$text=trim(preg_replace('/\s+/u',' ',$root->textContent));if(preg_match_all('/500/u',$text,$matches,PREG_OFFSET_CAPTURE)){foreach($matches[0] as $hit){$at=$hit[1];$start=max(0,$at-150);$piece=substr($text,$start,strlen('500')+300);$rec['body'][]=mb_convert_encoding($piece,'UTF-8','UTF-8');$bodycount++;}}}
 }
 fwrite($out,json_encode($rec,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE)."\n");curl_multi_remove_handle($mh,$c);curl_close($c);unset($active[$id]);$done++;if($done%100===0)fwrite(STDERR,"processed=$done/".count($queue)." http_non200=$bad field_hits=$heads body_hits=$bodycount\n");}
 if($active&&curl_multi_select($mh,1.0)===-1)usleep(100000);
}
fclose($out);curl_multi_close($mh);echo "SCANNED=$done HTTP_NON200=$bad FIELD_HITS=$heads BODY_HITS=$bodycount\n";
