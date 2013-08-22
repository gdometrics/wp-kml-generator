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
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$dirname = dirname($path);
	$ext = getExtension($path);

	if($dirname == '/' && $ext == 'kml'){
		$slug = trim(str_replace('/', '', chopExtension($path)));

		$db = new Wkgdb();
		$list = $db->get_list_by_slug($slug);

		if(!empty($list)){
			$enable_cache = get_option(WKG_ENABLE_CACHE, 1);
			$cache_time = intval(get_option(WKG_CACHE_TIME, 30));
			if($cache_time == 0){
				$enable_cache = 0;
			}else if($cache_time > 0){
				$cache_time *= 60;
			}else{
				$cache_time = 30 * 60;
			}
			
			require_once('functions/kml.class.php');

			$kml = new KML();

			if($enable_cache){
				$now = time();

				$cached_file = $db->get_cache_by_slug($slug, $now -$cache_time);
				//echo $cached_file.'1';

				if($cached_file){ // Cache found in DB
					echo $cached_file;

				}else{ // Cache not found in DB, create one
					$result = $kml->output($list);

					if(is_dir(WKG_TMP_PATH) && is_writable(WKG_TMP_PATH)){
						$cache_file_name = $now.'';

						if(!file_exists(WKG_TMP_PATH.'/'.$cache_file_name)){
							if(file_put_contents(WKG_TMP_PATH.'/'.$cache_file_name, $result) !== false){
								$data = array( 'index_id' => $list->id, 'cache_timestamp' => $cache_file_name );
								$db->insert_cache($data);

								header('Content-type: application/vnd.google-earth.kml+xml');
								echo $result;
								die();
							}
						}
					}

					// Cannot access temp folder, direct output
					$kml->output($list, true);
				}
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