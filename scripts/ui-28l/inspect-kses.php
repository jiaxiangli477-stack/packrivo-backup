<?php require '/var/www/html/wp-load.php';
$probe='<form><select><option>One</option></select><input type="text"><textarea>Note</textarea><style>.sample{color:red}</style></form>';
$clean=wp_kses_post($probe);echo json_encode(['input'=>$probe,'wp_kses_post'=>$clean,'content_save_filter'=>has_filter('content_save_pre','wp_filter_post_kses'),'content_filtered_filter'=>has_filter('content_filtered_save_pre','wp_filter_post_kses')],JSON_UNESCAPED_SLASHES)."\n";
