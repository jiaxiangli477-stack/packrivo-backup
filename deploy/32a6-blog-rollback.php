<?php
/** Roll back exactly the 32a-6 migration, after checking its backup and current state. */
if (!defined('WP_CLI') || !WP_CLI) exit(1);
array_unshift($args, 'rollback');
require __DIR__.'/32a6-blog-rewrite.php';
