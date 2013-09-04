<?php 
/*
Plugin Name: Simple KML Generator
Plugin URI: http://kingkong123.github.io/
Description: Plugin for generating KML files for Google Maps and Google Earth
Author: Kingkong123
Version: 1.0.1
Author URI: http://kingkong123.github.io/
*/

require_once('functions.php');

register_activation_hook(__FILE__, 'activate_wkg');

add_action('init', 'wkg_register_kml_tables', 1);
add_action('switch_blog', 'wkg_register_kml_tables');

add_action('init', 'kml_check');

function kml_check(){
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$dirname = dirname($path);
	$ext = getExtension($path);

	if($dirname == '/' && $ext == 'kml'){
		$slug = trim(str_replace('/', '', chopExtension($path)));

		$db = new Wkgdb();
		$list = $db->get_list_by_slug($slug);

		if(!empty($list)){
			$enable_cache = get_option(WKG_ENABLE_CACHE, 1);
			$cache_time = getCacheTime();
			
			require_once('functions/kml.class.php');

			$kml = new KML();

			if($enable_cache && $cache_time > 0){
				$now = time();

				$cached_file = $db->get_cache_by_slug($slug, $now - $cache_time);

				if($cached_file){ // Cache found in DB
					clearCache();

					if(file_exists(WKG_TMP_PATH.'/'.$cached_file)){
						$kml->set_header();
						echo file_get_contents(WKG_TMP_PATH.'/'.$cached_file);
						die();
					}
				}

				// Cache not found in DB, create one
				createCache($list, true);

				// Cannot access temp folder, direct output
				$kml->output($list, true);
				die();
			}else{
				$kml->output($list, true);
			}
			
			die();
		}
	}
}

// Add plugin meta
function wkg_plugin_links($links, $file) {  
    $plugin = plugin_basename(__FILE__);
  
    if ($file == $plugin) // only for this plugin  
        return array_merge( $links,   
            array( '<a href="admin.php?page='.WKG_KML_SETTINGS_SLUG.'">'.__(WKG_SETTINGS_TITLE).'</a>' ),
            array( '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=chan%2ekingsley%40gmail%2ecom&lc=US&item_name=Kingkong123%20Wordpress%20Plugins%20Projects&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHostedGuest" target="_blank">'.__('Donate').'</a>' )
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
	add_option(WKG_CACHE_TIME, 60);
	add_option(WKG_RM_CACHE_SIZE, 20);
	add_option(WKG_SHOW_SUPPORT, 0);

	// Create temp folder
	if(!file_exists(WKG_TMP_PATH)){
		mkdir(WKG_TMP_PATH, 0777, true);
	}else{
		if(!is_dir(WKG_TMP_PATH)){
			mkdir(WKG_TMP_PATH, 0777, true);
		}else if(!is_writable(WKG_TMP_PATH)){
			chmod(WKG_TMP_PATH, 0777);
		}
	}
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
		create_date bigint(20) NOT NULL default '0',
		PRIMARY KEY  (id),
		UNIQUE KEY (slug)
	) $charset_collate; ";
    
	dbDelta($sql_create_wkg_kml_index_table);
        
    $sql_create_wkg_kml_list_table = "CREATE TABLE {$wpdb->wkg_kml_list} (
        id bigint(20) NOT NULL AUTO_INCREMENT ,
        index_id bigint(20) NOT NULL,
        name VARCHAR( 100 ) NOT NULL ,
        address VARCHAR( 280 ) NOT NULL ,
        lat FLOAT( 10, 6 ) NOT NULL ,
        lng FLOAT( 10, 6 ) NOT NULL ,
        icon VARCHAR( 50 ) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate ;";
    
	dbDelta($sql_create_wkg_kml_list_table);

	$sql_create_wkg_kml_cache_table = "CREATE TABLE {$wpdb->wkg_kml_cache} (
		id bigint(20) NOT NULL AUTO_INCREMENT,
        index_id bigint(20) NOT NULL default '0' ,
        cache_timestamp bigint(20) NOT NULL default '0' ,
        PRIMARY KEY  (id)
    ) $charset_collate ;";
    
	dbDelta($sql_create_wkg_kml_cache_table);
}