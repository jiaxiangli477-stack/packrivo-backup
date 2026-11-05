<?php
/** Exact guarded edits to a separately backed-up legacy mu-plugin; never edits the input. */
if (PHP_SAPI !== 'cli' || $argc !== 4) {
    fwrite(STDERR, "Usage: php 32a-patch-legacy-moq.php <wave1|remediation|workspace-renderer|parent-functions> <input> <new-output>\n"); exit(2);
}
[$script,$kind,$input,$output]=$argv;
$maps=[
    'wave1'=>[
        ['Get a quote · MOQ 500','Get a quote · MOQ 250',1],
    ],
    'remediation'=>[
        ['500 pieces per product / design for custom printing; confirm supplier stock terms separately.','250 pieces per product / design for custom printing; confirm supplier stock terms separately.',2],
        ["'value' => '500 pieces per design'","'value' => '250 pieces per design'",1],
        ['MOQ 500 pieces per product/design. Quantities, sample scope and pricing are confirmed in the project quotation.','MOQ 250 pieces per product/design. Quantities, sample scope and pricing are confirmed in the project quotation.',1],
    ],
    'workspace-renderer'=>[
        ['Packaging option · MOQ 500 pieces per product / design','Packaging option · MOQ 250 pieces per product / design',1],
        ['<dt>Minimum order</dt><dd>500 pieces per product / design</dd>','<dt>Minimum order</dt><dd>250 pieces per product / design</dd>',1],
    ],
    'parent-functions'=>[
        ["['MOQ', '500 pieces per product / design.']","['MOQ', '250 pieces per product / design.']",1],
    ],
];
if (!isset($maps[$kind]) || !is_file($input) || file_exists($output)) {
    fwrite(STDERR,"Unknown kind, missing input, or output already exists; no file written.\n");exit(2);
}
$source=file_get_contents($input);
if ($source === false) { fwrite(STDERR,"Cannot read input; no file written.\n");exit(2); }
foreach ($maps[$kind] as [$old,$new,$expected]) {
    if (substr_count($source,$old)!==$expected) {
        fwrite(STDERR,"Unexpected match count for $kind; inspect source manually. No file written.\n");exit(1);
    }
}
$candidate=$source;
foreach ($maps[$kind] as [$old,$new,$expected]) $candidate=str_replace($old,$new,$candidate);
if (file_put_contents($output,$candidate,LOCK_EX)===false) { fwrite(STDERR,"Cannot write candidate.\n");exit(1); }
echo "$kind replacements=".array_sum(array_column($maps[$kind],2))." source_sha256=".hash('sha256',$source)." candidate_sha256=".hash('sha256',$candidate)."\n";
