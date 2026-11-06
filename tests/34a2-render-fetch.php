<?php
/** Fetch the 141 paths from the isolated 34a instance; never follow outside it. */
$map=$argv[1]??'';$out=$argv[2]??'';
if (!is_readable($map) || $out==='') { fwrite(STDERR,"usage: php fetch.php MAP.csv OUT.jsonl\n");exit(2); }
$in=fopen($map,'rb');$header=fgetcsv($in,0,',','"','');
if (is_array($header)) $header[0]=preg_replace('/^\xEF\xBB\xBF/','',(string)$header[0]);
$url_col=array_search('url',$header,true);if ($url_col===false) exit(2);
$writer=fopen($out,'wb');if (!$writer) exit(2);
$count=0;$bad=0;$seen=array();
function pk34a_fetch($url) {
    $headers=array();$c=curl_init($url);
    curl_setopt_array($c,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>45,
        CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_PROXY=>'',CURLOPT_NOPROXY=>'*',CURLOPT_ENCODING=>'',
        CURLOPT_HEADERFUNCTION=>static function($c,$line) use (&$headers){$headers[]=$line;return strlen($line);}));
    $body=curl_exec($c);$code=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);$error=curl_error($c);curl_close($c);
    $location='';foreach($headers as $line) if (stripos($line,'Location:')===0) $location=trim(substr($line,9));
    return array($code,is_string($body)?$body:'',$location,$error);
}
while (($row=fgetcsv($in,0,',','"',''))!==false) {
    if (empty($row[$url_col])) continue;
    $path=parse_url($row[$url_col],PHP_URL_PATH) ?: '/';if (substr($path,-1)!=='/')$path.='/';
    if (isset($seen[$path])) { fwrite(STDERR,"duplicate path $path\n");exit(2); }$seen[$path]=true;
    $url='http://packrivo-34a-wp'.$path;
    list($initial,$body,$location,$error)=pk34a_fetch($url);$status=$initial;$final=$url;
    if (in_array($initial,array(301,302,303,307,308),true) && $location!=='') {
        if (str_starts_with($location,'/') && !str_starts_with($location,'//')) $location='http://packrivo-34a-wp'.$location;
        $parts=parse_url($location);
        $host=$parts['host']??'';$scheme=$parts['scheme']??'';
        if (in_array($host,array('packrivo-main-wp','www.packrivo.com','packrivo.com'),true) && in_array($scheme,array('http','https'),true)) {
            // Preserve the actual redirect for reporting, but resolve its path
            // on this isolated 34a container, never on main or production.
            $final='http://packrivo-34a-wp'.($parts['path']??'/').(isset($parts['query'])?'?'.$parts['query']:'');
            list($status,$body,$unused,$error)=pk34a_fetch($final);
        } elseif ($scheme==='http' && $host==='packrivo-34a-wp' && (!isset($parts['port']) || $parts['port']===80)) {
            $final=$location;list($status,$body,$unused,$error)=pk34a_fetch($final);
        } else { $status=0;$body='';$error='external redirect blocked: '.$location; }
    }
    $title='';$meta='';$h1='';
    if ($body!=='') {
        $doc=new DOMDocument();libxml_use_internal_errors(true);$doc->loadHTML('<?xml encoding="utf-8" ?>'.$body);libxml_clear_errors();
        $xp=new DOMXPath($doc);$titles=$xp->query('//title');$h1s=$xp->query('//h1');
        $descs=$xp->query('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]');
        if ($titles && $titles->length) $title=$titles->item(0)->textContent;
        if ($descs && $descs->length) $meta=$descs->item(0)->getAttribute('content');
        if ($h1s && $h1s->length) $h1=$h1s->item(0)->textContent;
    }
    if ($status!==200) $bad++;
    $record=array('path'=>$path,'initial_status'=>$initial,'location'=>$location,'status'=>$status,'final_url'=>$final,'error'=>$error,
        'title'=>$title,'meta'=>$meta,'h1'=>$h1,'html'=>$body);
    fwrite($writer,json_encode($record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE)."\n");
    $count++;if ($count%25===0) fwrite(STDERR,"fetched=$count errors=$bad\n");
}
fclose($in);fclose($writer);fwrite(STDERR,"fetched=$count errors=$bad\n");
if ($count!==141 || $bad) exit(1);
