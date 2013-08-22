<?php

add_shortcode('kml_link', 'wkg_kml_link');
function wkg_kml_link($atts, $content = '') {
	extract(shortcode_atts(array('file' => '', 'show_icon' => 'yes'), $atts));

	$db = new Wkgdb();
	$kml = null;

	if($file != ''){
		$kml = $db->get_list_by_slug($file);

		if(!$kml){
			$file = chopExtension($file);
			$kml = $db->get_list_by_slug($file);
		}
	}

	wp_register_style( 'wkg-kml-styles', plugins_url("/css/wkg-kml-styles.css", dirname(__FILE__)) );
    wp_enqueue_style( 'wkg-kml-styles' );

	if($content == ''){
		$content = 'Download KML';
	}
	$content = __($content);

	if($kml){
		return '<span class="wkg-kml-link'.($show_icon == 'no'? ' wkg-no-icon': '').'"><a href="'.get_site_url().'/'.$file.'.kml'.'">'.$content.'</a></span>';
	}else{
		return '<span class="wkg-kml-not-exists">'.__('KML not exists').'</span>';
	}
}

add_shortcode('kml_list', 'wkg_kml_list');
function wkg_kml_list($atts) {
	extract(shortcode_atts(array('file' => '', 'show_title' => 'yes', 'download_link' => 'yes'), $atts));

	$db = new Wkgdb();
	$kml = null;
	$content = '';

	if($file != ''){
		$kml = $db->get_list_by_slug($file);

		if(!$kml){
			$file = chopExtension($file);
			$kml = $db->get_list_by_slug($file);
		}
	}

	wp_register_style( 'wkg-kml-styles', plugins_url("/css/wkg-kml-styles.css", dirname(__FILE__)) );
    wp_enqueue_style( 'wkg-kml-styles' );

	if($kml){
		$show_support = get_option(WKG_SHOW_SUPPORT, 0);

		$content = '<div class="wkg-kml-list-container">';

		if($show_title == 'yes'){
			$content .= '<h3>'.$kml->title.($download_link == 'yes'? '<span class="wkg-kml-link"><a href="'.get_site_url().'/'.$file.'.kml'.'">'.__('Download KML').'</a></span>': '').'</h3>';
		}else if($show_title == 'no' && $download_link == 'yes'){
			$content .= '<h3><span class="wkg-kml-link"><a href="'.get_site_url().'/'.$file.'.kml'.'">'.__('Download KML').'</a></span></h3>';
		}

		if(isset($kml->points) && !empty($kml->points)){
			$content .= '<ul class="wkg-kml-list">';

			foreach($kml->points as $point){
				$content .= '<li>';

				$content .= '<img src="'.WKG_ICONS_URL.'/'.$point->icon.'" />';
				$content .= '<h4>'.($point->name? $point->name: '').'</h4><br />';

				if( (isset($point->address) && !empty($point->address))
					&& (isset($point->lat) && !empty($point->lat) && isset($point->lng) && !empty($point->lng)) ){
					$query = 'q=loc:'.$point->lat.','.$point->lng;
					$content .= '<a href="'.WKG_GMAP_URL.$query.'" target="_blank">'.$point->address.'</a>';
				}else if((!isset($point->address) || empty($point->address))
					&& (isset($point->lat) && !empty($point->lat) && isset($point->lng) && !empty($point->lng))){
					$query = 'q=loc:'.$point->lat.','.$point->lng;
					$content .= '<a href="'.WKG_GMAP_URL.$query.'" target="_blank">'.$point->lat.','.$point->lng.'</a>';
				}else{
					$query = 'q='.urlencode($point->address);
					$content .= '<a href="'.WKG_GMAP_URL.$query.'" target="_blank">'.$point->address.'</a>';
				}

				$content .= '</li>';
			}

			$content .= '</ul>';
		}

		if($show_support){
			$content .= '<span class="wkg-kml-powered-by">Powered by '.WKG_PLUGIN_TITLE.'</span><br style="clear:both;" />';
		}
		
		$content .= '</div>';		

		return $content;
	}else{
		return '<span class="wkg-kml-not-exists">'.__('KML not exists').'</span>';
	}
}