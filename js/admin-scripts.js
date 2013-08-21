(function($) {
	$(function(){
		$('[name="wkg_kml_icon_select"]').prop('disabled', true).prop("checked", false);
		$('[name="wkg_kml_radio"]').prop("checked", false);

		$('.confirm').click(function(e){
			var content = 'The list will be delete, confirm?';

			if(typeof($(this).data('content')) != 'undefined'){
				content = $(this).data('content');
			}

            return confirm(content);
        });

		init_click_events();

		$('#wkg-kml-list li').each(function(i, el){
			var icon = $(this).find('[name*="wkg_kml_icon"]').val();

			$(this).find('img.wkg-icon-display').attr('src', WKG_ICONS_URL+'/'+icon);
			//WKG_ICONS_URL
		});
		
		if($('[name="'+WKG_FIELD_PREFIX+'title"]').length > 0){
			$('[name="'+WKG_FIELD_PREFIX+'title"]').stringToSlug({
				setEvents: 'keyup keydown blur',
				getPut: '#'+WKG_FIELD_PREFIX+'slug',
				space: '-'
			});
		}

		if($('[name="'+WKG_FIELD_PREFIX+'slug"]').length > 0){
			$('[name="'+WKG_FIELD_PREFIX+'slug"]').stringToSlug({
				setEvents: 'blur',
				getPut: '#'+WKG_FIELD_PREFIX+'slug',
				space: '-'
			});
		}
		

		// Add List Item
		$('.wkg-add-kml-item').click(function(e){
			e.preventDefault();

			$('#wkg-kml-list').append(list_item_template(wkg_kml_list_idx++));

			init_click_events();

			$('#wkg-kml-row-'+(wkg_kml_list_idx-1)).find('[name="wkg_kml_radio"]').trigger("click");
		});

		// Submit Form Button
		$('#wkg_kml_save').click(function(e){
			var success = true;

			// Simple validation
			if($('[name="'+WKG_FIELD_PREFIX+'title"]').val().length <= 0){
				console.log('1 '+$('[name="'+WKG_FIELD_PREFIX+'title"]').val().length);
				success = false;
			}

			if($('[name="'+WKG_FIELD_PREFIX+'slug"]').val().length <= 0){
				console.log('2 '+$('[name="'+WKG_FIELD_PREFIX+'slug"]').val().length);
				success = false;
			}

			if(success){
				$('#wkg-form').submit();
			}
		});

		// Sticky and Auto resize Icon List
		if($('#wkg-icon-list').length > 0){
			// 150

	        // grab the initial top offset of the navigation
	        var sticky_icon_list_offset_top = $('#wkg-icon-list').offset().top;
	        var init_width = $('#wkg-icon-list').width();
	        var init_left = $('#wkg-icon-list').offset().left;
	        var $window = $(window);
	        
	        // our function that decides weather the navigation bar should have "fixed" css position or not.
	        var sticky_icon_list = function(){
	            var scroll_top = $(window).scrollTop(); // our current vertical position from the top
	            
	            // if we've scrolled more than the navigation, change its position to fixed to stick to top,
	            // otherwise change it back to relative
	            if (scroll_top > (sticky_icon_list_offset_top - 20)) {
	            	var new_height = ($window.height() -30 -110);
            		var height = (new_height > 200)? new_height: 200;

	                $('#wkg-icon-list').css({ 
	                    'position': 'fixed',
	                    'width': init_width,
	                    'top': 30,
	                    'left': init_left,
	                    'height': height
	                });
	            }else{
	            	var new_height = ($window.height() -(sticky_icon_list_offset_top -scroll_top) -120);
            		var height = (new_height > 200)? new_height: 200;

	                $('#wkg-icon-list').css({
	                	'position': '',
	                    'width': '',
	                    'top': '',
	                    'left': '',
	                    'height': height
	                });
	            }  
	        };

	        // run our function on load
	        sticky_icon_list();

	        // and run it again every time you scroll
	        $(window).scroll(function() {
	             sticky_icon_list();
	        });
		}
	});

	function init_click_events(){
		var icon_field, img_field;
		$('[name="wkg_kml_radio"], [name="wkg_kml_icon_select"]').unbind('change');
		$('.wkg-remove-kml-row').unbind('click');
		$('[name*="wkg_kml_address"]').unbind('blur');

		if($("#wkg-kml-list").length > 0){
			$( "#wkg-kml-list" ).sortable({ handle: '.wkg-kml-list-handle' });
		}

		$('.wkg-remove-kml-row').click(function(e){
			e.preventDefault();

			var row_id = $(this).data('row');

			$('#wkg-kml-row-'+row_id).fadeOut(350, function(){
				$('[name="wkg_kml_icon_select"]').prop('disabled', true).prop("checked", false);
				$('#wkg-kml-row-'+row_id).remove();
			});
		});

		$('[name="wkg_kml_radio"]').change(function(){
			icon_field = $(this).parent().find('.wkg-icon-field');
			img_field = $(this).parent().find('.wkg-icon-display');

			if($('[name="wkg_kml_icon_select"]').prop('disabled')){
				$('[name="wkg_kml_icon_select"]').prop('disabled', false);
			}

			$('[name="wkg_kml_icon_select"][value="'+icon_field.val()+'"]').prop("checked", true);

			var selected_radio = $('#wkg-icon-list').find('input[value="'+icon_field.val()+'"]').parent().find('img');
			var selected_radio_position = selected_radio.position().top;
			var icon_list_scroll_top = $('#wkg-icon-list').scrollTop();
			var icon_list_height = $('#wkg-icon-list').height();

			if( selected_radio_position > icon_list_scroll_top && (selected_radio_position + selected_radio.height()) < (icon_list_scroll_top + icon_list_height) ){

			}else{
				if(selected_radio_position > (icon_list_scroll_top + icon_list_height)){
					var icon_img = selected_radio;
					var pos = (icon_img.position().top +icon_img.height() - icon_list_height) + icon_list_scroll_top;

					$('#wkg-icon-list').animate({
				        scrollTop: pos +2
				    }, 600);
				}else{
					$('#wkg-icon-list').animate({
				        scrollTop: (selected_radio_position + icon_list_scroll_top) -3
				    }, 600);
				}
			}
		});

		$('[name="wkg_kml_icon_select"]').change(function(){
			icon_field = $('[name="wkg_kml_radio"]:checked').parent().find('.wkg-icon-field');
			img_field = $('[name="wkg_kml_radio"]:checked').parent().find('.wkg-icon-display');

			//console.log(WKG_ICONS_URL+"/"+$(this).val());

			icon_field.val($(this).val());
			img_field.attr('src', WKG_ICONS_URL+"/"+$(this).val())
		});

		$('[name*="wkg_kml_address"]').blur(function(e){
			var $scope = $(this);
			var address = $.trim($(this).val());
			var encoded_address = $.trim(address.replace(/\s+/g, " "));
			var url = 'http://maps.google.com/maps/api/geocode/json?sensor=false&address='+encoded_address;

			$.getJSON(url, function(data){
				if(data.status == "OK"){
					var lat = data.results[0].geometry.location.lat;
					var lng = data.results[0].geometry.location.lng;

					$scope.parent().parent().parent().find('[name*=wkg_kml_lat]').val(lat);
					$scope.parent().parent().find('[name*=wkg_kml_lng]').val(lng);
				}
			});
		});
	}
})(jQuery);