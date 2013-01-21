<?php

//photoq only loads in admin section
define('WP_ADMIN', true);

require_once('wp-load.php');
require_once('wp-admin/includes/admin.php');

//call cronjob function of photoq plugin
$photoq->cronjob();

?>