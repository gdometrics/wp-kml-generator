<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

function wkg_deleteDirectory($dirPath) {
    if (is_dir($dirPath)) {
        $objects = scandir($dirPath);
        foreach ($objects as $object) {
            if ($object != "." && $object !="..") {
                if (filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir") {
                    deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dirPath . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
    reset($objects);
    rmdir($dirPath);
    }
}

delete_option(WKG_ENABLE_CACHE);
delete_option(WKG_CACHE_TIME);
delete_option(WKG_RM_CACHE_SIZE);
delete_option(WKG_SHOW_SUPPORT);

global $wpdb;
$table = "{$wpdb->prefix}wkg_kml_index";
$structure = "drop table if exists $table";
$wpdb->query($structure);

$table = "{$wpdb->prefix}wkg_kml_list";
$structure = "drop table if exists $table";
$wpdb->query($structure);

$table = "{$wpdb->prefix}wkg_kml_cache";
$structure = "drop table if exists $table";
$wpdb->query($structure);

wkg_deleteDirectory(WKG_TMP_PATH);