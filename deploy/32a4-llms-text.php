<?php
/** Extract server-rendered page text without fetching external resources. */
function pk32a4_page_text($html){
    $previous=libxml_use_internal_errors(true);$dom=new DOMDocument();
    $ok=$dom->loadHTML('<?xml encoding="UTF-8">'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
    libxml_clear_errors();libxml_use_internal_errors($previous);
    if(!$ok)throw new RuntimeException('Cannot parse HTML');
    $xp=new DOMXPath($dom);$title=trim((string)$xp->evaluate('string((//title)[1])'));
    if($title==='')throw new RuntimeException('Page has no title');
    $root=$xp->query('//main')->item(0)?:$xp->query('//body')->item(0);
    if(!$root)throw new RuntimeException('Page has no main or body');
    $query='.//text()[not(ancestor::script or ancestor::style or ancestor::nav or ancestor::header or ancestor::footer or ancestor::template or ancestor::noscript or ancestor::svg or ancestor::form or ancestor::*[@hidden] or ancestor::*[@aria-hidden="true"])]';
    $parts=[];foreach($xp->query($query,$root) as $node){$value=trim(preg_replace('/\s+/u',' ',$node->nodeValue));if($value!=='')$parts[]=$value;}
    if(!$parts)throw new RuntimeException('Page has no extractable text');
    $robots=[];foreach($xp->query('//meta[translate(@name,"ROBOTS","robots")="robots"]') as $m)$robots[]=$m->getAttribute('content');
    return ['title'=>$title,'text'=>implode("\n",$parts),'robots'=>implode(',',$robots)];
}
function pk32a4_old_moq($text){
    return preg_match('/\b(?:MOQ\s*(?::|is)?\s*500\b|minimum(?:\s+instant-quote|\s+order)?(?:\s+quantity)?\s*(?::|is)?\s*500\b|500[-\s]+piece(?:s)?\s+(?:custom\s+)?minimum\b|500\s+pieces?\s*MOQ\b|starting\s+quantity\s+is\s+500\b)/iu',$text)===1;
}
