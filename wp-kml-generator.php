<?php 
/*
Plugin Name: Simple KML Generator
Plugin URI: http://wcept.com/
Description: Plugin for displaying products from an OSCommerce shopping cart database
Author: Kingsley Chan
Version: 1.0
Author URI: http://wcept.com/
*/

require_once('functions.php');

register_activation_hook(__FILE__, 'activate_wkg');

add_action('init', 'wkg_register_kml_tables', 1);
add_action('switch_blog', 'wkg_register_kml_tables');

add_action('init', 'kml_check');

function kml_check(){
	//echo $_SERVER['REQUEST_URI'];
	//echo '<br />';

	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$dirname = dirname($path);
	$ext = getExtension($path);

	if($dirname == '/' && $ext == 'kml'){
		$slug = trim(str_replace('/', '', chopExtension($path)));

		$db = new Wkgdb();
		$list = $db->get_list_by_slug($slug);

		if(!empty($list)){
			require_once('functions/kml.class.php');

			$kml = new KML();
			$kml->output($list);
			
			die();
		}
	}
}

// Add plugin meta
function wkg_plugin_links($links, $file) {  
    $plugin = plugin_basename(__FILE__);  
  
    if ($file == $plugin) // only for this plugin  
        return array_merge( $links,   
            array( '<a href="admin.php?page='.WKG_KML_SETTINGS_SLUG.'">'.__(WKG_SETTINGS_TITLE).'</a>' )
        );
    return $links;  
}  
  
add_filter( 'plugin_row_meta', 'wkg_plugin_links', 10, 2 );

/*
 * Activate Plugin
 */
function activate_wkg(){
	wkg_create_kml_tables();

	add_option(WKG_ENABLE_CACHE, 1);
	add_option(WKG_CACHE_TIME, 30);
}
/*
 * Database Tables
 */
function wkg_register_kml_tables(){
    global $wpdb;
    $wpdb->wkg_kml_index = "{$wpdb->prefix}wkg_kml_index";
    $wpdb->wkg_kml_list = "{$wpdb->prefix}wkg_kml_list";
    $wpdb->wkg_kml_cache = "{$wpdb->prefix}wkg_kml_cache";
}

/**
 * Create tables
*/
function wkg_create_kml_tables(){
	global $wpdb;
	global $charset_collate;

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
	wkg_register_kml_tables();

	$sql_create_wkg_kml_index_table = "CREATE TABLE {$wpdb->wkg_kml_index} (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		user_id INT NOT NULL default '0' ,
		title varchar(50) NOT NULL default '' ,
		slug varchar(50) NOT NULL default '',
		create_date INT NOT NULL default '0',
		PRIMARY KEY  (id),
		UNIQUE KEY (slug)
	) $charset_collate; ";
    
	dbDelta($sql_create_wkg_kml_index_table);
        
    $sql_create_wkg_kml_list_table = "CREATE TABLE {$wpdb->wkg_kml_list} (
        id bigint(20) NOT NULL AUTO_INCREMENT ,
        index_id INT NOT NULL,
        name VARCHAR( 100 ) NOT NULL ,
        address VARCHAR( 280 ) NOT NULL ,
        lat FLOAT( 10, 6 ) NOT NULL ,
        lng FLOAT( 10, 6 ) NOT NULL ,
        icon VARCHAR( 50 ) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate ;";
    
	dbDelta($sql_create_wkg_kml_list_table);
}