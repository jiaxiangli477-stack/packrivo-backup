<?php
/** Fetch only same-site English page-sitemap HTML and write a staged text export. */
if(PHP_SAPI!=='cli'||$argc<5){fwrite(STDERR,"Usage: php 32a4-generate-llms-full.php <origin> <host-header> <public-origin> <new-output> [concurrency]\n");exit(2);}
require_once __DIR__.'/32a4-llms-text.php';
[$script,$origin,$host,$public,$output]=$argv;$origin=rtrim($origin,'/');$public=rtrim($public,'/');$limit=max(1,min(4,(int)($argv[5]??3)));
$u=parse_url($origin);$public_url=parse_url($public);$host_name=parse_url('http://'.$host,PHP_URL_HOST);
if(!is_array($u)||!in_array($u['scheme']??'',['http','https'],true)||empty($u['host'])||isset($u['user'])||isset($u['pass'])||!empty($u['query'])||!empty($u['fragment'])||!empty($u['path'])||!preg_match('/\A[A-Za-z0-9.:-]+\z/',$host)||!is_array($public_url)||!in_array($public_url['scheme']??'',['http','https'],true)||empty($public_url['host'])||isset($public_url['user'])||isset($public_url['pass'])||!empty($public_url['query'])||!empty($public_url['fragment'])||!empty($public_url['path'])||!$host_name||!str_starts_with($output,'/')||file_exists($output)){
 fwrite(STDERR,"Invalid origin, host or output; use a new absolute staging file.\n");exit(2);
}
function pk32a4_curl($url,$host){
 $c=curl_init($url);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>90,CURLOPT_HTTPHEADER=>['Host: '.$host,'Accept: text/html,application/xml'],CURLOPT_USERAGENT=>'Packrivo-llms-generator/32a4']);return $c;
}
$c=pk32a4_curl($origin.'/page-sitemap.xml',$host);$xml=curl_exec($c);$status=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);$err=curl_error($c);curl_close($c);
if($xml===false||$status!==200){fwrite(STDERR,"Sitemap request failed HTTP $status $err\n");exit(3);}
$previous=libxml_use_internal_errors(true);$map=simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);libxml_clear_errors();libxml_use_internal_errors($previous);
if(!$map){fwrite(STDERR,"Invalid page sitemap\n");exit(3);}
$map->registerXPathNamespace('s','http://www.sitemaps.org/schemas/sitemap/0.9');$paths=[];
foreach($map->xpath('//s:url/s:loc')?:[] as $loc){
 try{$path=pk32a4_sitemap_path((string)$loc,$host_name);}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(3);}
 if($path!==null)$paths[$path]=true;
}
$paths=array_keys($paths);if(!$paths){fwrite(STDERR,"Empty sitemap\n");exit(3);}
$multi=curl_multi_init();$active=[];$next=0;$done=0;$parts=[];$errors=[];
$start=static function()use(&$next,$paths,$origin,$host,$multi,&$active){
 $i=$next++;$ch=pk32a4_curl($origin.$paths[$i],$host);$active[spl_object_id($ch)]=[$ch,$i];curl_multi_add_handle($multi,$ch);
};
while($next<count($paths)&&count($active)<$limit)$start();
do{
 do{$rc=curl_multi_exec($multi,$running);}while($rc===CURLM_CALL_MULTI_PERFORM);
 if($rc!==CURLM_OK){$errors[]='curl_multi error '.$rc;break;}
 while($info=curl_multi_info_read($multi)){
  $ch=$info['handle'];$id=spl_object_id($ch);[$ignore,$i]=$active[$id];$path=$paths[$i];
  $body=curl_multi_getcontent($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
  if($info['result']!==CURLE_OK||$code!==200)$errors[]=$path.' HTTP '.$code.' '.curl_error($ch);
  else try{
   $p=pk32a4_page_text($body);
   if(preg_match('/\bnoindex\b/i',$p['robots']))throw new RuntimeException('sitemap page is noindex');
   if(pk32a4_old_moq($p['title']."\n".$p['text']))throw new RuntimeException('stale 500-piece MOQ');
   $parts[$i]='## '.$p['title']."\n\nURL: ".$public.$path."\n\n".$p['text']."\n";
  }catch(Throwable $e){$errors[]=$path.' '.$e->getMessage();}
  curl_multi_remove_handle($multi,$ch);curl_close($ch);unset($active[$id]);$done++;
  if($done%25===0)echo 'processed='.$done.'/'.count($paths).' errors='.count($errors)."\n";
  if($next<count($paths))$start();
 }
 if($active&&curl_multi_select($multi,1.0)===-1)usleep(100000);
}while($active);
curl_multi_close($multi);
if($errors){foreach($errors as $e)fwrite(STDERR,"ERROR $e\n");fwrite(STDERR,'FAILED pages='.count($errors)."; no output written\n");exit(4);}
ksort($parts);if(count($parts)!==count($paths)){fwrite(STDERR,"Incomplete page set; no output written\n");exit(4);}
$result="# Packrivo — English page text\n\n> Server-rendered main content from the current English page sitemap. Blog posts, navigation, scripts and forms are excluded.\n\n> Pages: ".count($parts)."\n\n---\n\n".implode("\n---\n\n",$parts);
$f=@fopen($output,'x');if(!$f){fwrite(STDERR,"Cannot create staged output\n");exit(5);}
$written=fwrite($f,$result);fclose($f);if($written!==strlen($result)){fwrite(STDERR,"Incomplete staged output\n");exit(5);}
echo 'pages='.count($parts).' bytes='.strlen($result).' sha256='.hash('sha256',$result)." stale_moq=0\n";
