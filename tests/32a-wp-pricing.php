<?php
$settings=PKQ_Plugin::settings();
$base=['style'=>'rigid','material'=>'rigidboard','print'=>'none','finish'=>'foil','length'=>9.5,'width'=>9.5,'height'=>4];
foreach ([249,250,500,1000,3000] as $qty) {
    $result=PKQ_Pricing::price($base+['quantity'=>$qty],$settings);
    if (is_wp_error($result)) echo "rigid none+foil qty=$qty ERROR " . $result->get_error_code() . ' ' . json_encode($result->get_error_data()) . "\n";
    else echo "rigid none+foil qty=$qty " . json_encode($result,JSON_UNESCAPED_SLASHES) . "\n";
}
foreach ([500,1000] as $qty) {
    $result=PKQ_Pricing::price(array_merge($base,['quantity'=>$qty,'print'=>'full-color','finish'=>'matte']),$settings);
    echo "rigid full-color+matte qty=$qty " . json_encode($result,JSON_UNESCAPED_SLASHES) . "\n";
}
$result=PKQ_Pricing::price(['style'=>'carton','material'=>'white','print'=>'one-color','finish'=>'none','length'=>8,'width'=>8,'height'=>3,'quantity'=>250],$settings);
echo 'carton white one-color none qty=250 ' . json_encode($result,JSON_UNESCAPED_SLASHES) . "\n";
