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