<?php
/** Restore missing form controls from the existing parent asset in damaged native content. */
function pk_repair_inquiry_controls($content, $reference) {
    if (stripos($content, '<form') !== false || stripos($content, 'id="inquiry-product"') !== false) return $content;
    if (!preg_match('~<select\b(?=[^>]*\bid="inquiry-product")[\s\S]*?</select>~i', $reference, $select)) return $content;
    if (!preg_match('~<label>Product / packaging type \*([\s\S]*?)</label>~i', $content, $broken)) return $content;
    // Reject an unrelated/corrupted text shape rather than silently dropping it.
    $original = preg_replace('/\s+/u', '', html_entity_decode(strip_tags($broken[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $candidate = preg_replace('/\s+/u', '', html_entity_decode(strip_tags($select[0]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $j = 0; for ($i = 0, $n = strlen($candidate); $i < $n && $j < strlen($original); $i++) if ($candidate[$i] === $original[$j]) $j++;
    if ($original === '' || $j !== strlen($original)) return $content;
    $fields = [
        'Approximate dimensions (include unit) *' => 'inquiry-dimensions',
        'Expected quantity *' => 'inquiry-quantity',
        'Destination country *' => 'inquiry-country',
        'Work email *' => 'inquiry-email',
        'Material direction' => 'inquiry-material',
        'Printing / finish' => 'inquiry-printing',
        'Target date' => 'inquiry-date',
    ];
    $inputs = [];
    foreach ($fields as $label => $id) {
        if (substr_count($content, '<label>' . $label . '</label>') !== 1) return $content;
        if (!preg_match('~<input\b(?=[^>]*\bid="' . preg_quote($id, '~') . '")[^>]*>~i', $reference, $input)) return $content;
        $inputs[$label] = [$id, $input[0]];
    }
    if (!preg_match('~<div class="pr-contact-fields">[\s\S]*?</div>~i', $reference, $contacts)) return $content;
    if (!preg_match('~<div class="pr-upload-fields">[\s\S]*?</div>~i', $reference, $upload)) return $content;
    if (substr_count($content, '<fieldset>') !== 1 || !preg_match('~<button\b[^>]*\bid="build-brief"[^>]*>[\s\S]*?</button>~i', $content, $button)) return $content;
    if (!preg_match('~<details class="optional-fields">[\s\S]*?</details>~i', $content, $details)) return $content;
    $repaired = str_replace($details[0], str_replace('</details>', $contacts[0] . '</details>' . $upload[0], $details[0]), $content);
    $repaired = str_replace($broken[0], '<label for="inquiry-product">Product / packaging type *</label>' . $select[0], $repaired);
    foreach ($inputs as $label => [$id, $input]) $repaired = str_replace('<label>' . $label . '</label>', '<label for="' . $id . '">' . $label . '</label>' . $input, $repaired);
    $repaired = str_replace('<fieldset>', '<form id="inquiry-form" autocomplete="on"><fieldset>', $repaired);
    return str_replace($button[0], $button[0] . '</form>', $repaired);
}
