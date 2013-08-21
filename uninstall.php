<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

delete_option(WKG_ENABLE_CACHE);
delete_option(WKG_CACHE_TIME);

global $wpdb;
$table = "{$wpdb->prefix}wkg_kml_index";
$structure = "drop table if exists $table";
$wpdb->query($structure);

$table = "{$wpdb->prefix}wkg_kml_list";
$structure = "drop table if exists $table";
$wpdb->query($structure);