<?php

function wkg_admin_menu_init() {
    //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    add_menu_page(WKG_PLUGIN_TITLE, WKG_PLUGIN_TITLE, 'manage_options', WKG_KML_INDEX_SLUG, 'wkg_kml_generator_index_page', plugins_url("/img/kml_feed_small.png", dirname(__FILE__)), '123.13');
    
    add_submenu_page( WKG_KML_INDEX_SLUG, WKG_ADD_TITLE, WKG_ADD_TITLE, 'manage_options', WKG_KML_ADD_SLUG, 'wkg_add_kml_page' );
    add_submenu_page( WKG_KML_INDEX_SLUG, __(WKG_SETTINGS_TITLE), __(WKG_SETTINGS_TITLE), 'manage_options', WKG_KML_SETTINGS_SLUG, 'wkg_settings_kml_page' );

    add_action('admin_head', 'wkg_include_js_constants');
}

add_action( 'admin_menu', 'wkg_admin_menu_init' );
add_action('wp_loaded', 'wkg_process_kml_forms');

function wkg_kml_generator_index_page(){
    include('pagination.class.php');

    wp_enqueue_script("wkg-admin-scripts", plugins_url("/js/admin-scripts.js", dirname(__FILE__)), array('jquery'));

    wp_register_style('pagination-style', plugins_url('/css/digg-style-pagination.css', dirname(__FILE__)));
    wp_enqueue_style( 'pagination-style' );
    
    $message = '';
    $page = (isset($_GET['p'])? $_GET['p']: 1);
    
    $filters = array();
    if(isset($_GET['list-filter'])){
        
    }
    
    $db = new Wkgdb();
    $p = new pagination();
    
    $all_items = $db->get_all_list_count($filters);
    
    $p->items($all_items); //get_all_tracking_count
    $p->limit(WKG_PER_PAGE);
    $p->currentPage($page);
    $p->target('?page='.WKG_KML_INDEX_SLUG);
    $p->parameterName("p");
    
    $results = $db->get_kml_list($filters, WKG_PER_PAGE, (($page-1)*WKG_PER_PAGE));
    
    if(!empty($results)){
        $body = '';
        $table_body = '';
        
        foreach($results as $row){
            $table_body .= '<tr data-kml-slug="'.$row->slug.'">
                <td>
                    <a href="'.admin_url('admin.php?page='.WKG_KML_ADD_SLUG.'&fn=edit&list_id='.$row->id).'" class="row-title">'.$row->title.'</a>
                    <div class="extra_row"><a href="'.admin_url('admin.php?page='.WKG_KML_ADD_SLUG.'&fn=edit&list_id='.$row->id).'">'.__('Edit').'</a> | <a href="'.admin_url('admin.php?page='.WKG_KML_ADD_SLUG.'&fn=delete&list_id='.$row->id).'" class="confirm">'.__('Delete').'</a></div>
                </td>
                <td>
                    '.get_site_url().'/'.$row->slug.'.kml<br />
                    <span class="wkg-italic">Download Link:</span> <span class="wkg-shortcode-wrap">[kml_link file="'.$row->slug.'.kml"]Content[/kml_link]</span><br /><span class="wkg-italic">KML List:</span> <span class="wkg-shortcode-wrap">[kml_list file="'.$row->slug.'.kml"]</span>
                </td>
                <td>'.$row->list_items.'</td>
                <td>'.date(get_option('date_format').' '.get_option('time_format'), $row->create_date).'</td>
            </tr>';
        }
        
        if(isset($_GET['status'])){
            $message = '';
            
            switch($_GET['status']){
                case 'add_success':
                    $message = 'KML list created.';
                    break;
                case 'add_error':
                    $message = 'Fail to create KML list, please retry.';
                    break;
                case 'edit_success':
                    $message = 'Updated KML list.';
                    break;
                case 'edit_error':
                    $message = 'Fail to updated KML list, please retry.';
                    break;
                case 'not_exists_error':
                    $message = 'Item not exists, please retry.';
                    break;
                case 'delete_success':
                    $message = 'Removed KML list.';
                    break;
                case 'delete_error':
                    $message = 'Fail to remove KML list, please retry.';
                    break;
            }
        }
        
        $items_count_div = '<div class="tablenav top">
            <div class="tablenav-pages one-page">
                <span class="displaying-num">'.__('Total').' '.$all_items.' '.__('rows').'</span>
            </div>
        </div>';

        $embed_gmap = _wkg_embed_gmap();
        
        $body .= $embed_gmap.$items_count_div.'
            <table class="widefat wkg-kml-list-table" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>'.__('Title').'</th>
                        <th>'.__('URL').' / '.__('Shortcodes').'</th>
                        <th>'.__('List Items').'</th>
                        <th>'.__('Create Date').'</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>'.__('Title').'</th>
                        <th>'.__('URL').' / '.__('Shortcodes').'</th>
                        <th>'.__('List Items').'</th>
                        <th>'.__('Create Date').'</th>
                    </tr>
                </tfoot>
                <tbody>
                   '.$table_body.'
                </tbody>
            </table><br style="clear:both;" />
            '.$p->show(false);
    }else{
        $body = 'No KML list created.';
    }

    $body .= _wkg_get_admin_footer();
    
    $add_button = '<a class="add-new-h2" href="'.admin_url('admin.php?page='.WKG_KML_ADD_SLUG).'">'.__('Add').'</a>';

    echo _wkg_wrap_page(WKG_PLUGIN_TITLE, $body, $add_button, $message);
}

function wkg_add_kml_page(){
    $db = new Wkgdb();

    $list_id = (isset($_GET['list_id'])? $_GET['list_id']: 0);
    $function = (isset($_GET['fn'])? $_GET['fn']: 'add');

    $error_fields = array();

    if(isset($_POST['wkg_kml_save'])){
        $data = array();

        $success = true;

        // START Get list data
        if(isset($_POST[WKG_FIELD_PREFIX.'title']) && !empty($_POST[WKG_FIELD_PREFIX.'title'])){
            $data['title'] = $_POST[WKG_FIELD_PREFIX.'title'];
        }else{
            $data['title'] = $_POST[WKG_FIELD_PREFIX.'title'];
            $success = false;

            $error_fields['title'] = 'Title field is required.';
        }

        if( isset($_POST[WKG_FIELD_PREFIX.'slug']) && !empty($_POST[WKG_FIELD_PREFIX.'slug'])){
            if(!$db->slug_exists($_POST[WKG_FIELD_PREFIX.'slug'])){
                $data['slug'] = $_POST[WKG_FIELD_PREFIX.'slug'];
            }else{
                $data['slug'] = $_POST[WKG_FIELD_PREFIX.'slug'];
                $success = false;

                $error_fields['slug'] = 'Slug exists.';
            }
        }else{
            $data['slug'] = $_POST[WKG_FIELD_PREFIX.'slug'];
            $success = false;

            $error_fields['slug'] = 'Slug field is required.';
        }

        if(isset($_POST[WKG_FIELD_PREFIX.'icon']) && !empty($_POST[WKG_FIELD_PREFIX.'icon'])){
            $icons = $_POST[WKG_FIELD_PREFIX.'icon'];
            $loc_name = $_POST[WKG_FIELD_PREFIX.'loc_name'];
            $address = $_POST[WKG_FIELD_PREFIX.'address'];
            $lat = $_POST[WKG_FIELD_PREFIX.'lat'];
            $lng = $_POST[WKG_FIELD_PREFIX.'lng'];

            $data['points'] = array();

            foreach($icons as $key => $icon){
                if((isset($loc_name[$key]) && !empty($loc_name[$key]))
                    || (isset($address[$key]) && !empty($address[$key]))
                    || (isset($lat[$key]) && !empty($lat[$key]))
                    || (isset($lng[$key]) && !empty($lng[$key]))){

                    $data['points'][$key]['icon'] = $icon;

                    if(isset($loc_name[$key]) && !empty($loc_name[$key])){
                        $data['points'][$key]['name'] = $loc_name[$key];
                    }else{
                        $data['points'][$key]['name'] = '';
                    }

                    if(isset($address[$key]) && !empty($address[$key])){
                        $data['points'][$key]['address'] = $address[$key];
                    }else{
                        $data['points'][$key]['address'] = '';
                    }

                    if(isset($lat[$key]) && !empty($lat[$key])){
                        $data['points'][$key]['lat'] = $lat[$key];
                    }else{
                        $data['points'][$key]['lat'] = '';
                    }

                    if(isset($lng[$key]) && !empty($lng[$key])){
                        $data['points'][$key]['lng'] = $lng[$key];
                    }else{
                        $data['points'][$key]['lng'] = '';
                    }
                }
            }
        }
        // END Get list data

        if(!$success){
            // Repopulate the from
            _wkg_add_kml_page($data, $function, $error_fields);
        }
    }else{
        if($function == 'add'){
            _wkg_add_kml_page();
        }else if($function == 'edit' && $list_id){
            _wkg_add_kml_page($db->get_list_by_id($list_id), $function);
        }else{
            // error
        }
    }
}

function wkg_process_kml_forms(){
    $db = new Wkgdb();
    $function = (isset($_GET['fn'])? $_GET['fn']: 'add');
    $list_id = (isset($_GET['list_id'])? $_GET['list_id']: 0);

    if(isset($_POST['wkg_kml_save'])){
        $data = array();

        $success = true;

        // START Get list data
        if(isset($_POST[WKG_FIELD_PREFIX.'title']) && !empty($_POST[WKG_FIELD_PREFIX.'title'])){
            $data['title'] = $_POST[WKG_FIELD_PREFIX.'title'];
        }else{
            $data['title'] = $_POST[WKG_FIELD_PREFIX.'title'];
            $success = false;
        }

        if( isset($_POST[WKG_FIELD_PREFIX.'slug']) && !empty($_POST[WKG_FIELD_PREFIX.'slug'])){
            if(!$db->slug_exists($_POST[WKG_FIELD_PREFIX.'slug'], $list_id)){
                $data['slug'] = $_POST[WKG_FIELD_PREFIX.'slug'];
            }else{
                $data['slug'] = $_POST[WKG_FIELD_PREFIX.'slug'];
                $success = false;
            }
        }else{
            $data['slug'] = $_POST[WKG_FIELD_PREFIX.'slug'];
            $success = false;
        }

        if(isset($_POST[WKG_FIELD_PREFIX.'icon']) && !empty($_POST[WKG_FIELD_PREFIX.'icon'])){
            $icons = $_POST[WKG_FIELD_PREFIX.'icon'];
            $loc_name = $_POST[WKG_FIELD_PREFIX.'loc_name'];
            $address = $_POST[WKG_FIELD_PREFIX.'address'];
            $lat = $_POST[WKG_FIELD_PREFIX.'lat'];
            $lng = $_POST[WKG_FIELD_PREFIX.'lng'];

            $data['points'] = array();

            foreach($icons as $key => $icon){
                if((isset($loc_name[$key]) && !empty($loc_name[$key]))
                    || (isset($address[$key]) && !empty($address[$key]))
                    || (isset($lat[$key]) && !empty($lat[$key]))
                    || (isset($lng[$key]) && !empty($lng[$key]))){

                    $data['points'][$key]['icon'] = $icon;

                    if(isset($loc_name[$key]) && !empty($loc_name[$key])){
                        $data['points'][$key]['name'] = $loc_name[$key];
                    }else{
                        $data['points'][$key]['name'] = '';
                    }

                    if(isset($address[$key]) && !empty($address[$key])){
                        $data['points'][$key]['address'] = $address[$key];
                    }else{
                        $data['points'][$key]['address'] = '';
                    }

                    if(isset($lat[$key]) && !empty($lat[$key])){
                        $data['points'][$key]['lat'] = $lat[$key];
                    }else{
                        $data['points'][$key]['lat'] = '';
                    }

                    if(isset($lng[$key]) && !empty($lng[$key])){
                        $data['points'][$key]['lng'] = $lng[$key];
                    }else{
                        $data['points'][$key]['lng'] = '';
                    }
                }
            }
        }
        // END Get list data
        
        if($success){
            $enable_cache = get_option(WKG_ENABLE_CACHE, 1);

            if($function == 'add'){ // Add list data to db
                $success = $db->insert_kml_list($data);
                if($success){
                    // Redirect with success message
                    wp_redirect( admin_url('admin.php?page='.WKG_KML_INDEX_SLUG.'&status=add_success') );
                }else{
                    // Redirect with error message
                    wp_redirect( admin_url('admin.php?page='.WKG_KML_INDEX_SLUG.'&status=add_error') );
                }
                exit();
            }else if($function == 'edit' && $list_id){ // Update list data to db
                $list_data = $db->get_list_by_id($list_id);

                if($list_data){
                    $success = $db->update_kml_list($list_id, $data);

                    if($enable_cache && $success){
                        clearCache();

                        $list = $db->get_list_by_slug($data['slug']);
                        createCache($list);
                    }

                    if($success){
                        // Redirect with success message
                        wp_redirect( admin_url('admin.php?page='.WKG_KML_INDEX_SLUG.'&status=edit_success') );
                    }else{
                        // Redirect with error message
                        wp_redirect( admin_url('admin.php?page='.WKG_KML_INDEX_SLUG.'&status=edit_error') );
                    }
                    
                }else{
                    // Redirect with error message List not exists
                    wp_redirect( admin_url('admin.php?page='.WKG_KML_INDEX_SLUG.'&status=not_exists_error') );
                }
                exit();
            }
        }
    }else{
        if($function == 'delete' && $list_id){
            if($db->delete_kml_list($list_id)){
                wp_redirect( admin_url('admin.php?page='.WKG_KML_INDEX_SLUG.'&status=delete_success') );
            }else{
                wp_redirect( admin_url('admin.php?page='.WKG_KML_INDEX_SLUG.'&status=delete_error') );
            }
            exit();
        }
    }
}

function wkg_settings_kml_page(){
    $message = '';
    $enable_cache = get_option(WKG_ENABLE_CACHE, 1);
    $cache_time = get_option(WKG_CACHE_TIME, 60);
    $remove_size = get_option(WKG_RM_CACHE_SIZE, 20);
    $show_support = get_option(WKG_SHOW_SUPPORT, 0);

    if(isset($_POST['wkg_kml_save'])){
        if(isset($_POST[WKG_FIELD_PREFIX.'cache'])){
            $enable_cache = $_POST[WKG_FIELD_PREFIX.'cache'];
            update_option(WKG_ENABLE_CACHE, $enable_cache);
        }

        if(isset($_POST[WKG_FIELD_PREFIX.'time'])){
            $cache_time = intval($_POST[WKG_FIELD_PREFIX.'time']);
            update_option(WKG_CACHE_TIME, $cache_time);
        }

        if(isset($_POST[WKG_FIELD_PREFIX.'rm_cache'])){
            $remove_size = intval($_POST[WKG_FIELD_PREFIX.'rm_cache']);
            update_option(WKG_RM_CACHE_SIZE, $remove_size);
        }

        if(isset($_POST[WKG_FIELD_PREFIX.'support'])){
            $show_support = $_POST[WKG_FIELD_PREFIX.'support'];
            update_option(WKG_SHOW_SUPPORT, $show_support);
        }

        $message = 'Settings saved.';
    }
    $page_title = WKG_SETTINGS_TITLE;

    $save_button = '<input type="submit" name="'.WKG_FIELD_PREFIX.'save" id="'.WKG_FIELD_PREFIX.'save" value="'.__('Save').'" tabindex="4" class="button-primary">';

    $content = '
        <h3>Cache Control</h3>
        <div class="wkg-setting-row">
            <input type="hidden" name="'.WKG_FIELD_PREFIX.'cache" id="'.WKG_FIELD_PREFIX.'cache" value="0" />
            <label for="'.WKG_FIELD_PREFIX.'cache">'.__('Enable Cache').'</label><input type="checkbox" name="'.WKG_FIELD_PREFIX.'cache" id="'.WKG_FIELD_PREFIX.'cache" value="1" '.($enable_cache == 1? 'checked': '').' />
            <span class="wkg-row-description">Caching KML can reduce the server load time after the KML is cached.</span>
        </div>
        <div class="wkg-setting-row">
            <label for="'.WKG_FIELD_PREFIX.'time">'.__('Cache period').'</label>
            <input type="text" name="'.WKG_FIELD_PREFIX.'time" id="'.WKG_FIELD_PREFIX.'time" value="'.$cache_time.'" />
            <span class="wkg-row-description">Cache refresh time. ('.__('Minutes').')</span>
        </div>
        <div class="wkg-setting-row">
            <label for="'.WKG_FIELD_PREFIX.'rm_cache">'.__('Cache Size').'</label>
            <input type="text" name="'.WKG_FIELD_PREFIX.'rm_cache" id="'.WKG_FIELD_PREFIX.'rm_cache" value="'.$remove_size.'" />
            <span class="wkg-row-description">When the cache files exceed this size, the plugin will remove old cache files. ('.__('Default 20').')</span>
        </div>
        <hr />
        <h3>Support the plugin</h3>
        <div class="wkg-setting-row">
            <input type="hidden" name="'.WKG_FIELD_PREFIX.'support" id="'.WKG_FIELD_PREFIX.'support" value="0" />
            <label for="'.WKG_FIELD_PREFIX.'support">'.__('Show "Powered by"').'</label><input type="checkbox" name="'.WKG_FIELD_PREFIX.'support" id="'.WKG_FIELD_PREFIX.'support" value="1" '.($show_support == 1? 'checked': '').' />
            <span class="wkg-row-description">Support the plugin by adding a "Powered by" text in shortcodes.</span>
        </div>
        <div class="wkg-setting-row">
            <label for="">'.__('Make a donation').'</label><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=chan%2ekingsley%40gmail%2ecom&lc=US&item_name=Kingkong123%20Wordpress%20Plugins%20Projects&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHostedGuest" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" style="vertical-align: top;" /></a>
            <span class="wkg-row-description">Support the plugin by making a donation.</span>
        </div>
    '.$save_button._wkg_get_admin_footer();

    echo _wkg_wrap_page($page_title, $content, '', $message, $_SERVER['QUERY_STRING'], 'icon-options-general');
}

function _wkg_add_kml_page($data = array(), $fn = 'add', $errors = array()){
    if($fn == 'add'){
        $page_title = WKG_ADD_TITLE;
    }else if($fn == 'edit'){
        $page_title = WKG_EDIT_TITLE;
        $exists = (empty($data)? false: true);

        if(!$exists){
            // Redirect with message List not exists
            wp_redirect( admin_url('admin.php?page='.WKG_KML_INDEX_SLUG.'&status=not_exists_error') );
        }
    }

    $error_message = '';
    $error_fields = array();

    if(!empty($errors)){
        $error_size = sizeof($errors);
        $idx = 0;

        foreach($errors as $error => $message){
            $error_message .= $message;
            $error_fields[] = $error;

            $idx++;

            if($idx < $error_size){
                $error_message .= '<br />';
            }
        }

        if(!empty($error_message)){
            $error_message = '<div class="error settings-error below-h2"><p>'.$error_message.'</p></div>';
        }
    }

    $save_button = '<input type="button" name="'.WKG_FIELD_PREFIX.'save" id="'.WKG_FIELD_PREFIX.'save" value="'.__('Save').'" tabindex="4" class="button-primary">';

    wp_enqueue_script('jquery');
    wp_enqueue_script("string-to-slug", plugins_url("/js/jquery.stringToSlug.min.js", dirname(__FILE__)), array('jquery'));
    
    if(is_ssl()){
        wp_enqueue_script("jquery-ui", 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js', array('jquery'));
    }else{
        wp_enqueue_script("jquery-ui", 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js', array('jquery'));
    }

    wp_enqueue_script("wkg-templates", plugins_url("/js/templates.js", dirname(__FILE__)));
    wp_enqueue_script("wkg-admin-scripts", plugins_url("/js/admin-scripts.js", dirname(__FILE__)), array('jquery', 'jquery-ui', 'string-to-slug', 'wkg-templates'));

    $list_title = '<div id="titlediv"><input type="text" autocomplete="off" class="'.(in_array('title', $error_fields)? 'error_field': '').'" id="title" value="'.(isset($data['title'])? $data['title']: '').'" size="20" name="'.WKG_FIELD_PREFIX.'title"></div>';
    $kml_link = '<div id="wkg_slug_div"><label for="'.WKG_FIELD_PREFIX.'slug">KML Link:</label> '.get_site_url().'/<input type="text" class="'.(in_array('slug', $error_fields)? 'error_field': '').'" name="'.WKG_FIELD_PREFIX.'slug" id="'.WKG_FIELD_PREFIX.'slug" autocomplete="off" value="'.(isset($data['slug'])? $data['slug']: '').'" size="20" />.kml</div>';

    $embed_gmap = _wkg_embed_gmap();

    $list_body = '<div id="wkg_body">'._get_icon_list().'<div id="wkg-loc-list">'._get_kml_list((isset($data['points'])? $data['points']: array())).'</div><br style="clear: both;" /></div>';

    $content = $list_title.$error_message.$kml_link.$embed_gmap.$list_body._wkg_get_admin_footer();

    echo _wkg_wrap_page($page_title, $content, $save_button, '', $_SERVER['QUERY_STRING']);
}

function _get_kml_list($points = array()){
    $first_icon = _wkg_get_first_icon();
    $list_idx = 0;

    $result = '<h3>Locations <a class="add-new-h2 wkg-add-kml-item" href="#">'.__('Add').'</a></h3>
        <ul id="wkg-kml-list">';

    if(empty($points)){
        $points = array(
            array(
                'icon' => $first_icon['name'],
                'name' => '',
                'address' => '',
                'lat' => '',
                'lng' => ''
                )
            );
    }
    
    foreach($points as $point){
        $result .= '<li id="wkg-kml-row-'.$list_idx.'">
            <div class="wkg-kml-list-handle"></div>
            <label>

            <input type="radio" name="'.WKG_FIELD_PREFIX.'radio" id="'.WKG_FIELD_PREFIX.'radio" value="" class="hidden" />
            
            <table class="wkg-marker-list-table">
                <tr>
                    <td rowspan="2">
                        <input type="hidden" class="wkg-icon-field" name="'.WKG_FIELD_PREFIX.'icon['.$list_idx.']" id="'.WKG_FIELD_PREFIX.'icon['.$list_idx.']" value="'.$point['icon'].'" />
                        <img class="wkg-icon-display" src="'.WKG_ICONS_URL.'/'.$point['icon'].'" />
                    </td>
                    <td><span>Name: </span></td>
                    <td colspan="3"><input type="text" class="" name="'.WKG_FIELD_PREFIX.'loc_name['.$list_idx.']" id="'.WKG_FIELD_PREFIX.'loc_name['.$list_idx.']" value="'.$point['name'].'" size="38" /></td>
                    <td>Lat: </td>
                    <td><input type="text" name="'.WKG_FIELD_PREFIX.'lat['.$list_idx.']" id="'.WKG_FIELD_PREFIX.'lat['.$list_idx.']" value="'.$point['lat'].'" size="22" /></td>
                    <td rowspan="2" valign="top"><a href="#" class="wkg-remove-kml-row" data-row="'.$list_idx.'">X</a></td>
                </tr>
                <tr>
                    <td><span>Address: </span></td>
                    <td colspan="3"><input type="text" name="'.WKG_FIELD_PREFIX.'address['.$list_idx.']" id="'.WKG_FIELD_PREFIX.'address['.$list_idx.']" value="'.$point['address'].'" size="38" /></td>
                    <td>Lng: </td>
                    <td><input type="text" name="'.WKG_FIELD_PREFIX.'lng['.$list_idx.']" id="'.WKG_FIELD_PREFIX.'lng['.$list_idx.']" value="'.$point['lng'].'" size="22" /></td>
                </tr>
            </table>
            </label>
        </li>';
        $list_idx++;
    }
    

    $result .= '</ul><script>var wkg_kml_list_idx = '.$list_idx.';</script>';

    return $result;
}

function _wkg_get_first_icon(){
    if ($handle = opendir(dirname(__FILE__).'/../img/icons')) {
        while (false !== ($entry = readdir($handle))) {
            $ext = substr($entry, strrpos($entry, '.')+1);

            if(strtolower($ext) == 'png'){
                $icons[] = $entry;
            }
        }

        closedir($handle);
    }

    if(!empty($icons)){
        natsort($icons);
        foreach($icons as $icon){
            $result = array();
            $result['url'] = WKG_ICONS_URL."/$icon";
            $result['name'] = $icon;
            
            return $result;
        }
    }
}
function _get_icon_list(){
	$icons = array();
    $result = '';

	if ($handle = opendir(dirname(__FILE__).'/../img/icons')) {
	    while (false !== ($entry = readdir($handle))) {
	    	$ext = substr($entry, strrpos($entry, '.')+1);

	    	if(strtolower($ext) == 'png'){
	    		$icons[] = $entry;
	    	}
	    }

	    closedir($handle);
	}

	if(!empty($icons)){
		natsort($icons);
        $result .= '<div id="wkg-icons-div">';
        $result .= '<h3>Marker Icons</h3>';
		$result .= '<ul id="wkg-icon-list">';

		foreach($icons as $icon){
			$result .= '<li><label><input type="radio" class="hidden" name="'.WKG_FIELD_PREFIX.'icon_select" id="'.WKG_FIELD_PREFIX.'icon_select" value="'.$icon.'" /><img src="'.WKG_ICONS_URL."/$icon".'" /></label></li>';
		}

		$result .= '</ul><div class="wkg-icon-remarks">Icons by <a href="http://mapicons.nicolasmollet.com/">Maps Icons Collection</a></div></div>';
	}

	return $result;
}

function wkg_include_js_constants(){
    $first_icon = _wkg_get_first_icon();

    echo '<script>
        var WKG_KML_INDEX_SLUG = "'.WKG_KML_INDEX_SLUG.'", WKG_KML_ADD_SLUG = "'.WKG_KML_ADD_SLUG.'", WKG_PLUGIN_TITLE = "'.WKG_PLUGIN_TITLE.'",
            WKG_ADD_TITLE = "'.WKG_ADD_TITLE.'", WKG_FIELD_PREFIX = "'.WKG_FIELD_PREFIX.'", WKG_TMP_PATH = "'.WKG_TMP_PATH.'", WKG_TMP_URL = "'.WKG_TMP_URL.'";
        var wkg_first_icon_url = "'.$first_icon['url'].'", wkg_first_icon_name = "'.$first_icon['name'].'";
        var WKG_ROOT_URL = "'.WKG_ROOT_URL.'", WKG_ICONS_URL = "'.WKG_ICONS_URL.'", WKG_SITE_URL = "'.get_site_url().'";
    </script>';
}

function _wkg_get_admin_footer(){
    return '<hr class="wkg-line-break" />
        <div class="wkg-admin-footer">
            <span class="wkg-donate-link"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=chan%2ekingsley%40gmail%2ecom&lc=US&item_name=Kingkong123%20Wordpress%20Plugins%20Projects&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHostedGuest" target="_blank">Make a donation</a></span>
            <span class="wkg-brought-to-you">brought to you by <a href="http://kingkong123.github.io/">kingkong123</a></span>
            <br style="clear:both;" />
        </div>';
}

function _wkg_embed_gmap(){
    if(is_ssl()){
        wp_enqueue_script("gmap-v3", 'https://maps.googleapis.com/maps/api/js?sensor=false', array('jquery'));
    }else{
        wp_enqueue_script("gmap-v3", 'http://maps.googleapis.com/maps/api/js?sensor=false', array('jquery'));
    }

    wp_enqueue_script("admin-gmap-scripts", plugins_url("/js/admin-gmap-scripts.js", dirname(__FILE__)), array('gmap-v3'));
    
    return '<div id="wkg-gmap-container">
        <div id="wkg-gmap-canvas"></div>
    </div>
    <div class="wkg-gmap-container-handle"></div>';
}

function _wkg_wrap_page($title = '', $content = "", $button = '', $message = "", $slug = '', $icon = 'icon-edit', $method = 'POST'){
	wp_register_style( 'admin-styles', plugins_url("/css/admin-styles.css", dirname(__FILE__)) );
    wp_enqueue_style( 'admin-styles' );

    if($slug == ''){
        $slug = $_SERVER['QUERY_STRING'];
    }
    $title = '<div class="icon32 icon32-posts-post" id="'.$icon.'"><br></div><h2>'.$title.' '.$button.'</h2>';
    
    return '<div class="wrap wkg-wrap"><form id="wkg-form" action="'.admin_url('admin.php?'.$slug).'" method="'.$method.'" accept-charset="utf-8">'.$title.($message != ''? _wkg_wrap_message($message): '').$content.'</form></div>';
}

function _wkg_wrap_message($message = ''){
    $class = 'updated';
    if( isset($_GET['add_error']) || isset($_GET['add_error']) || isset($_GET['not_exists_error']) || isset($_GET['delete_error']) ){
        $class = 'error';
    }
    return '<div class="'.$class.' settings-error" id="setting-error-settings_updated"><p><strong>'.__($message).'</strong></p></div>';
}