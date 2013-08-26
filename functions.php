<?php

require_once('functions/constants.php');
require_once('functions/wkgdb.class.php');
require_once('functions/shortcodes.php');
require_once('functions/widgets.php');

require_once('functions/admin-functions.php');

function getCacheTime(){
	$cache_time = intval(get_option(WKG_CACHE_TIME, 60));

	if($cache_time > 0){
		$cache_time *= 60;
	}else{
		$cache_time = 60 * 60;
	}

	return $cache_time;
}

function createCache($data = '', $echo = false){
	if($data != ''){
		require_once('functions/kml.class.php');
		
		$kml = new KML();
		$db = new Wkgdb();
		$now = time();

		$result = $kml->output($data);

		if(is_dir(WKG_TMP_PATH) && is_writable(WKG_TMP_PATH)){
			$cache_file_name = $now.'';

			if(!file_exists(WKG_TMP_PATH.'/'.$cache_file_name)){
				if(file_put_contents(WKG_TMP_PATH.'/'.$cache_file_name, $result) !== false){
					$data = array( 'index_id' => $list->id, 'cache_timestamp' => $cache_file_name );
					$db->insert_cache($data);

					if($echo){
						$kml->set_header();
						echo $result;

						die();
					}
				}
			}
		}
	}
}
function clearCache(){
	$cache_size = intval(get_option(WKG_RM_CACHE_SIZE, 20));
	$cache_time = getCacheTime();

	if(file_exists(WKG_TMP_PATH) && is_dir(WKG_TMP_PATH) && is_writable(WKG_TMP_PATH)){
		$files = array_diff( scandir(WKG_TMP_PATH), array(".", "..") );
		$num_files = count($files);

		if($num_files > $cache_size){
			$db = new Wkgdb();
			$cache_period = time() - $cache_time;

			if ($handle = opendir(WKG_TMP_PATH)) {
			    while (false !== ($entry = readdir($handle))) {
			    	if($entry != '.' && $entry != '..'){
			    		if(intval($entry) < 1000 || intval($entry) < $cache_period){
			    			unlink(WKG_TMP_PATH.'/'.$entry);
			    		}
			    	}
			    }

			    closedir($handle);
			}

			$db->clear_cache($cache_period);
		}
	}
}

function chopExtension($filename = ''){
	return substr($filename, 0, strrpos($filename, '.'));
}

function getExtension($filename = ''){
	return substr($filename, strrpos($filename, '.')+1);
}