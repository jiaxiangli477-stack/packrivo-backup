<?php
/** Offline check for the exact homepage template formatter, with a fake dictionary. */
function pkv4_i18n_t($key, $kinds = []) {
    $map = [
        'Samples %s days' => 'عينات %s أيام',
        '%s days' => '%s أيام',
        'Sourcing fee %s' => 'رسوم %s',
        '%s pieces, sourcing fee included' => 'شامل الرسوم %s قطعة',
        'Rigid greyboard · %1$s coated paper over %2$s greyboard' => 'لوح %2$s وورق %1$s',
        '%s recycled greyboard' => 'لوح معاد %s',
        'Folding carton · %s' => 'كرتون %s',
        'Print setup is waived from %1$s pieces; die setup is waived from %2$s pieces.' => 'القالب %2$s والطباعة %1$s',
    ];
    return $map[$key] ?? $key;
}
function esc_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$source = file_get_contents(__DIR__ . '/../packrivo-wp/packrivo-home-v4/front-page.php');
if (!preg_match('/\$pk33a_ltr_values = static function \(\$template, array \$values\) \{.*?\n\};/s', $source, $match)) exit(2);
eval($match[0]);
$cases = [
    ['Samples %s days',['5–10'],'عينات',1],
    ['%s days',['5–10'],'أيام',1],
    ['Sourcing fee %s',['8–15%'],'رسوم',1],
    ['%s pieces, sourcing fee included',['250'],'قطعة',1],
    ['Rigid greyboard · %1$s coated paper over %2$s greyboard',['157g','600–2000g'],'وورق',2],
    ['%s recycled greyboard',['100%'],'معاد',1],
    ['Folding carton · %s',['300–400 gsm'],'كرتون',1],
    ['Print setup is waived from %1$s pieces; die setup is waived from %2$s pieces.',['2,500','5,000'],'والطباعة',2],
];
foreach ($cases as [$template,$values,$expected,$count]) {
    $html=$pk33a_ltr_values($template,$values);
    if (strpos($html,$expected)===false || substr_count($html,'<bdi dir="ltr">')!==$count || preg_match('/%\d*\$?s/', $html)) exit(1);
    foreach ($values as $value) if (strpos($html,esc_html($value))===false) exit(1);
}
echo "PASS synthetic Arabic templates=8 placeholders preserved; numbers isolated LTR\n";
