<?php
$ids=get_posts(['post_type'=>'page','post_status'=>'publish','posts_per_page'=>-1,'meta_key'=>'_packrivo_remediation_product','fields'=>'ids','orderby'=>'ID','order'=>'ASC']);$data=[];foreach($ids as $id){$p=get_post($id);$data[]=['post'=>(array)$p,'meta'=>get_post_meta($id)];}echo 'product_pages=',count($ids),' content_and_meta_sha256=',hash('sha256',wp_json_encode($data)),"\n";
