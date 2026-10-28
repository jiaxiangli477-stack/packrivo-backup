<?php
require '/var/www/html/wp-load.php';
require get_stylesheet_directory() . '/inc/repair-inquiry.php';
$old = get_page_by_path('inquiry')->post_content;
$ref = file_get_contents(get_template_directory() . '/packrivo-static/assets/inquiry-controls.html');
$new = pk_repair_inquiry_controls($old, $ref);
$norm = static function ($s) { return preg_replace('/\s+/u', '', html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8')); };
$a = $norm($old); $b = $norm($new); $j = 0;
for ($i = 0, $n = strlen($b); $i < $n && $j < strlen($a); $i++) if ($b[$i] === $a[$j]) $j++;
$ok = $j === strlen($a) && pk_repair_inquiry_controls($ref, $ref) === $ref && pk_repair_inquiry_controls($old, '') === $old;
$counts=[];foreach(['form','select','input','textarea'] as $tag)$counts[$tag]=preg_match_all('~<'.$tag.'\b~i',$new);
echo json_encode(['original_text_bytes_preserved'=>$j,'original_text_bytes_total'=>strlen($a),'intact_form_unchanged'=>pk_repair_inquiry_controls($ref,$ref)===$ref,'missing_reference_noop'=>pk_repair_inquiry_controls($old,'')===$old,'repaired_counts'=>$counts,'pass'=>$ok],JSON_UNESCAPED_SLASHES)."\n";
if (!$ok) exit(1);
