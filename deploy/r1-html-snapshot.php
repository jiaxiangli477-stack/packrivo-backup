<?php
/** Capture status, redirect and body hash for a fixed R1 path list through an explicit internal origin. */
function r1_html_record($path,$status,$body,$location){return ['path'=>$path,'status'=>$status,'location'=>$location,'sha256'=>hash('sha256',$body),'bytes'=>strlen($body)];}
if(defined('R1_HTTP_SNAPSHOT_TEST_ONLY')&&R1_HTTP_SNAPSHOT_TEST_ONLY)return;
if(PHP_SAPI!=='cli'||$argc!==3||file_exists($argv[2])){fwrite(STDERR,"usage: php r1-html-snapshot.php paths.json NEW.json\n");exit(2);}
try{
 $origin=getenv('R1_AUDIT_ORIGIN')?:'http://packrivo-r1-proxy';$host=getenv('R1_AUDIT_HOST')?:'www.packrivo.com';$o=parse_url($origin);
 if(!is_array($o)||!in_array($o['scheme']??'',['http','https'],true)||empty($o['host'])||isset($o['user'])||isset($o['pass'])||!empty($o['path'])||isset($o['query'])||isset($o['fragment'])||!preg_match('/^[A-Za-z0-9.:-]+$/D',$host))throw new InvalidArgumentException('invalid audit origin or host');
 $paths=json_decode((string)file_get_contents($argv[1]),true);if(!is_array($paths)||count($paths)!==30||count(array_unique($paths))!==30)throw new InvalidArgumentException('expected 30 unique paths');$records=[];
 foreach($paths as $path){if(!is_string($path)||!preg_match('~^/(?!/)[^\r\n]*$~',$path)||strpos($path,'://')!==false)throw new InvalidArgumentException('unsafe path');$c=curl_init($origin.$path);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>90,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_PROXY=>'',CURLOPT_HTTPHEADER=>['Host: '.$host]]);$body=curl_exec($c);$status=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);$location=(string)curl_getinfo($c,CURLINFO_REDIRECT_URL);$error=curl_error($c);curl_close($c);if(!is_string($body)||$status===0)throw new RuntimeException('HTTP failure '.$path.' '.$error);$records[]=r1_html_record($path,$status,$body,$location);}
 $data=json_encode($records,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);$f=fopen($argv[2],'x');if(!$f||fwrite($f,$data)!==strlen($data)||!fclose($f))throw new RuntimeException('cannot write snapshot');echo 'HTML_SNAPSHOT pages='.count($records).' sha256='.hash('sha256',$data)."\n";
}catch(Throwable $e){fwrite(STDERR,'HTML_SNAPSHOT_STOP '.$e->getMessage()."\n");exit(1);}
