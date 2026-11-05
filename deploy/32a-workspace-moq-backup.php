<?php
require_once __DIR__.'/32a-workspace-moq-lib.php';
try {pkpw32a_write_backup();} catch (Throwable $e) {WP_CLI::error('Backup failed: '.$e->getMessage());}
