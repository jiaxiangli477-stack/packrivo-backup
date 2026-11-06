<?php
/** Plugin Name: Packrivo R1 approved samples social description projection */
function r1_moq_social_description($id,$description,$projection){
 $old='Order a sample of your custom box or pouch to check size, print and finish before your 500-piece order. Get a price.';
 $new='Order a sample of your custom box or pouch to check size, print and finish before your 250-piece order. Get a price.';
 return (int)$id===2884 && $description===$old && $projection===$new ? $new : $description;
}
if(defined('R1_MOQ_RENDER_TEST_ONLY')&&R1_MOQ_RENDER_TEST_ONLY)return;
if(!defined('ABSPATH'))return;
add_filter('wpseo_opengraph_desc',static function($description){$id=(int)get_queried_object_id();return r1_moq_social_description($id,$description,$id?get_post_meta($id,'_packrivo_description',true):'');},1100);
add_filter('wpseo_twitter_description',static function($description){$id=(int)get_queried_object_id();return r1_moq_social_description($id,$description,$id?get_post_meta($id,'_packrivo_description',true):'');},1100);
