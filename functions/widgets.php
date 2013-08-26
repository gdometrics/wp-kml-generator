<?php

function wkg_widgets_init(){
	register_widget( 'WKG_link_widget' );
	register_widget( 'WKG_list_widget' );
}
add_action( 'widgets_init', 'wkg_widgets_init' );


class WKG_link_widget extends WKG_Widget {
	function __construct() {
		$widget_ops = array('classname' => WKG_PLUGIN_NAME, 'description' => __( "Display KML Download Link") );
                
		parent::__construct(WKG_KML_WIDGET_DL_SLUG, __('KML Download Link'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args, EXTR_SKIP);
		$this->_enqueue_style();

        $show_icon = apply_filters( 'icon', empty( $instance['icon'] ) ? 'yes' : $instance['icon'], $instance );
        $file = apply_filters( 'kml_filename', empty( $instance['kml_filename'] ) ? '' : $instance['kml_filename'], $instance );
        $kml = null;

        if($file != ''){
			$kml = $this->db->get_list_by_slug($file);
		}

        echo $before_widget;
		
		if($kml){
			echo '
	        	<span class="wkg-kml-link '.($show_icon == 'no'? 'wkg-no-icon': '').'">
	        		<a href="'.get_site_url().'/'.$file.'.kml">Download KML</a>
	        	</span>';
		}else{
			echo '<span class="wkg-kml-not-exists">'.__('KML not exists').'</span>';
		}

		echo $after_widget;
	}

	function form( $instance ) {
		$kml_list = array();

		$kml_objs = $this->db->get_kml_list();

		$kml_list[] = '==Select KML==';
		foreach($kml_objs as $kml_item){
			$kml_list[$kml_item->slug] = $kml_item->title;
		}

		$kml_file = '';
        if ( isset( $instance[ 'kml_filename' ] ) ) {
        	$kml_file = strip_tags($instance[ 'kml_filename' ]);
		}

		$show_icon = 'yes';
		if( isset( $instance['icon'] ) ){
			$show_icon = strip_tags($instance[ 'icon' ]);
		}
        
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'kml_filename' ); ?>"><?php _e( 'KML List:' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'kml_filename' ); ?>" name="<?php echo $this->get_field_name( 'kml_filename' ); ?>">
				<?php echo $this->_create_options($kml_list, $kml_file); ?>
			</select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'icon' ); ?>"><?php _e( 'Show icon:' ); ?></label> 
            <input type="checkbox" id="<?php echo $this->get_field_id('icon'); ?>" name="<?php echo $this->get_field_name('icon'); ?>" value="yes" <?php echo ($show_icon == 'yes'? 'checked': '') ?> />
        </p>
<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'icon' => 'no', 'kml_filename'=>'' ));
		$instance['kml_filename'] = strip_tags($new_instance['kml_filename']);
        $instance['icon'] = $new_instance['icon'];

		return $instance;
	}

	
}

class WKG_list_widget extends WKG_Widget {
	function __construct() {
		$widget_ops = array('classname' => WKG_PLUGIN_NAME, 'description' => __( "Display KML List") );
                
		parent::__construct(WKG_KML_WIDGET_LIST_SLUG, __('KML List'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args, EXTR_SKIP);
		$this->_enqueue_style();

		$file = apply_filters( 'kml_filename', empty( $instance['kml_filename'] ) ? '' : $instance['kml_filename'], $instance );
        $show_title = apply_filters( 'show_title', empty( $instance['show_title'] ) ? 'yes' : $instance['show_title'], $instance );
        $download_link = apply_filters( 'download_link', empty( $instance['download_link'] ) ? 'yes' : $instance['download_link'], $instance );

        $kml = null;

        if($file != ''){
			$kml = $this->db->get_list_by_slug($file);
		}

        echo $before_widget;
		
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

			echo $content;
		}else{
			echo '<span class="wkg-kml-not-exists">'.__('KML not exists').'</span>';
		}

		echo $after_widget;
	}

	function form( $instance ) {
		$kml_list = array();

		$kml_objs = $this->db->get_kml_list();

		$kml_list[] = '==Select KML==';
		foreach($kml_objs as $kml_item){
			$kml_list[$kml_item->slug] = $kml_item->title;
		}

		$kml_file = '';
        if ( isset( $instance[ 'kml_filename' ] ) ) {
        	$kml_file = strip_tags($instance[ 'kml_filename' ]);
		}

		$show_title = 'yes';
		if( isset( $instance['show_title'] ) ){
			$show_title = strip_tags($instance[ 'show_title' ]);
		}

        $download_link = 'yes';
		if( isset( $instance['download_link'] ) ){
			$download_link = strip_tags($instance[ 'download_link' ]);
		}
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'kml_filename' ); ?>"><?php _e( 'KML List:' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'kml_filename' ); ?>" name="<?php echo $this->get_field_name( 'kml_filename' ); ?>">
				<?php echo $this->_create_options($kml_list, $kml_file); ?>
			</select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Show title:' ); ?></label> 
            <input type="checkbox" id="<?php echo $this->get_field_id('show_title'); ?>" name="<?php echo $this->get_field_name('show_title'); ?>" value="yes" <?php echo ($show_title == 'yes'? 'checked': '') ?> />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'download_link' ); ?>"><?php _e( 'Show download link:' ); ?></label> 
            <input type="checkbox" id="<?php echo $this->get_field_id('download_link'); ?>" name="<?php echo $this->get_field_name('download_link'); ?>" value="yes" <?php echo ($download_link == 'yes'? 'checked': '') ?> />
        </p>
<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'show_title' => 'no', 'download_link' => 'no', 'kml_filename'=>'' ));
		$instance['kml_filename'] = strip_tags($new_instance['kml_filename']);
        $instance['show_title'] = $new_instance['show_title'];
        $instance['download_link'] = $new_instance['download_link'];

		return $instance;
	}

	
}

class WKG_Widget extends WP_Widget{
	protected $db = null;

	function __construct($slug = '', $text = '', $widget_ops = array()) {
        $this->db = new Wkgdb();

		parent::__construct($slug, $text, $widget_ops);
	}

	function _enqueue_style(){
		wp_register_style( 'wkg-kml-styles', plugins_url("/css/wkg-kml-styles.css", dirname(__FILE__)) );
    	wp_enqueue_style( 'wkg-kml-styles' );
	}

	function _create_options($list = array(), $selected = ''){
		$result = '';

		if(!empty($list)){
			foreach($list as $key => $value){
				$result .= '<option value="'.$key.'" '.($selected == $key? 'selected': '').'>'.$value.'</option>';
			}
		}

		return $result;
	}
}
