function list_item_template(idx){
    if(typeof(idx) == 'undefined'){
        idx = 0;
    }

    return '<li id="wkg-kml-row-'+idx+'">\
        <div class="wkg-kml-list-handle"></div>\
        <label>\
\
        <input type="radio" name="'+WKG_FIELD_PREFIX+'radio" id="'+WKG_FIELD_PREFIX+'radio" value="" class="hidden" />\
        \
        <table class="wkg-marker-list-table">\
            <tr>\
                <td rowspan="2">\
                    <input type="hidden" class="wkg-icon-field" name="'+WKG_FIELD_PREFIX+'icon['+idx+']" id="'+WKG_FIELD_PREFIX+'icon['+idx+']" value="'+wkg_first_icon_name+'" />\
                    <img class="wkg-icon-display" src="'+wkg_first_icon_url+'" />\
                </td>\
                <td><span>Name: </span></td>\
                <td colspan="3"><input type="text" class="wkg_field_required" name="'+WKG_FIELD_PREFIX+'loc_name['+idx+']" id="'+WKG_FIELD_PREFIX+'loc_name['+idx+']" value="" size="38" /></td>\
                <td>Lat: </td>\
                <td><input type="text" name="'+WKG_FIELD_PREFIX+'lat['+idx+']" id="'+WKG_FIELD_PREFIX+'lat['+idx+']" value="" size="22" /></td>\
                <td rowspan="2" valign="top"><a href="#" class="wkg-remove-kml-row" data-row="'+idx+'">X</a></td>\
            </tr>\
            <tr>\
                <td><span>Address: </span></td>\
                <td colspan="3"><input type="text" name="'+WKG_FIELD_PREFIX+'address['+idx+']" id="'+WKG_FIELD_PREFIX+'address['+idx+']" value="" size="38" /></td>\
                <td>Lng: </td>\
                <td><input type="text" name="'+WKG_FIELD_PREFIX+'lng['+idx+']" id="'+WKG_FIELD_PREFIX+'lng['+idx+']" value="" size="22" /></td>\
            </tr>\
        </table>\
        </label>\
    </li>';
}