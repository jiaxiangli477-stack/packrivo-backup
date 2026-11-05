<?php
require_once __DIR__.'/32a-workspace-moq-lib.php';
if(empty($args[0]))WP_CLI::error('Pass the verified original MOQ backup CSV path.');
try {pkpw32a_apply_backup($args[0],'migrate');} catch (Throwable $e) {WP_CLI::error('Migration failed: '.$e->getMessage());}
