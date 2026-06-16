<?php
defined( 'ABSPATH' ) || exit;
$current = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'vixion-seo-audit';
$stats   = vx_db_get_stats();
?>
<div class="vx-wrap">
<div class="vx-inner">
