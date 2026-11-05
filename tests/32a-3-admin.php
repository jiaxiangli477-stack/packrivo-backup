<?php
$admins=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);
if(!$admins)WP_CLI::error('No local admin');
wp_set_current_user((int)$admins[0]);
ob_start();(new PKPW_Product_Admin())->render_editor();$html=ob_get_clean();
$prompt=strpos($html,'MOQ 250 pieces per product / design')!==false;
$hidden=strpos($html,'name="record[commercial][moq]" value="250"')!==false;
echo 'ADMIN_MOQ_PROMPT_250='.($prompt?'true':'false')."\n";
echo 'ADMIN_HIDDEN_MOQ_250='.($hidden?'true':'false')."\n";
if(!$prompt||!$hidden)WP_CLI::error('Admin MOQ check failed');
