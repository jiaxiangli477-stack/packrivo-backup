<?php
/** Pure transforms used by the reviewed 27g import. */
function pk27g_replace_h1($content, $heading)
{
    if (preg_match_all('~(<h1\b[^>]*>)(.*?)(</h1>)~is', $content) !== 1) {
        throw new RuntimeException('Expected exactly one stored H1.');
    }
    return preg_replace_callback('~(<h1\b[^>]*>)(.*?)(</h1>)~is', static function ($m) use ($heading) {
        return $m[1] . htmlspecialchars($heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . $m[3];
    }, $content, 1);
}

function pk27g_body_hash($content)
{
    return hash('sha256', pk27g_replace_h1($content, '__PK27G_H1__'));
}

function pk27g_read_json($file)
{
    $raw = file_get_contents($file);
    if ($raw === false) throw new RuntimeException('Cannot read ' . $file);
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) throw new RuntimeException('Invalid JSON: ' . $file);
    return $data;
}

function pk27g_inputs($root, $scope = 'pending130')
{
    $items = [];
    $hashes = [];
    for ($i = 1; $i <= 5; $i++) {
        $name = 'batch-0' . $i . '.json';
        $file = $root . '/dsh-out/' . $name;
        $part = pk27g_read_json($file);
        if (count($part) !== 26) throw new RuntimeException('Unexpected batch size: ' . $name);
        $hashes[$name] = hash_file('sha256', $file);
        foreach ($part as $row) {
            foreach (['slug','old_title','new_title','new_h1','new_description'] as $key) {
                if (!isset($row[$key]) || !is_string($row[$key]) || $row[$key] === '') throw new RuntimeException('Missing input field: ' . $key);
            }
            if (isset($items[$row['slug']])) throw new RuntimeException('Duplicate input slug.');
            if ($row['new_title'] !== $row['new_h1'] . ' | Packrivo') throw new RuntimeException('Title/H1 mismatch.');
            $items[$row['slug']] = $row;
        }
    }
    if ($scope === 'all134') {
        $csv = $root . '/scripts/product-buyers-27e/full-inventory.csv';
        $fh = fopen($csv, 'r'); if (!$fh) throw new RuntimeException('Cannot read prior inventory.');
        $head = fgetcsv($fh); $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]); $old = [];
        while (($line = fgetcsv($fh)) !== false) { $r = array_combine($head,$line); $old[$r['slug']]=$r['old_title']; }
        fclose($fh);
        $hashes['prior-full-inventory.csv']=hash_file('sha256',$csv);
        foreach (['products.json','third.json','fourth.json'] as $name) {
            $file=$root . '/scripts/product-buyers-27e/' . $name;
            $hashes['prior-'.$name]=hash_file('sha256',$file);
            foreach(pk27g_read_json($file) as $row) {
                if(isset($items[$row['slug']]) || !isset($old[$row['slug']])) throw new RuntimeException('Invalid prior product source.');
                $items[$row['slug']]=['slug'=>$row['slug'],'old_title'=>$old[$row['slug']], 'new_title'=>$row['title'].' | Packrivo','new_h1'=>$row['title'],'new_description'=>$row['description']];
            }
        }
        if(count($items)!==134) throw new RuntimeException('Expected all 134 approved products.');
    } elseif ($scope !== 'pending130') throw new RuntimeException('Unknown import scope.');
    return ['items'=>$items,'hashes'=>$hashes,'hash'=>hash('sha256', json_encode($hashes))];
}
