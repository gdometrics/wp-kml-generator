(function($) {
	var gmap = null;

	var markersArray = [];

	$(function(){
		gmap_initialize();

		$('.wkg-gmap-container-handle').click(function(e){
			if( $('#wkg-gmap-container').hasClass('expand') ){
				$('#wkg-gmap-container').removeClass('expand');

				$('#wkg-gmap-container').css( 'height', '' );
			}else{
				var height = Math.floor($(window).height()*0.38);

				$('#wkg-gmap-container').addClass('expand');
				$('#wkg-gmap-canvas').css( 'height', height );
				$('#wkg-gmap-container').css( 'height', height );
			}

			setTimeout(function(){
				$(window).trigger('resize').trigger('scroll');
			},500);
		});

		$(window).on('gmap-marker-selected', function(e, data){
			if($('.wkg-gmap-container-handle').is(':visible')){
				deleteOverlays();

				if( (!data.lat || !data.lng) && data.address ){
					var latLng = geoEncode(data.address);

					if(latLng != null){
						data.lat = latLng.lat;
						data.lng = latLng.lng;
					}
				}

				addMarker(data);

				if( !(!data.lat || !data.lng) ){
					showOverlays();
					gmap.panTo(new google.maps.LatLng(data.lat,data.lng));

					if(gmap.getZoom() < 10){
						gmap.setZoom(12);
					}
				}
			}
		});

		$(window).on('gmap-kml-row-selected', function(e, data){
			if($('.wkg-gmap-container-handle').is(':visible')){
				var kmlFileName = data.filename;
				var url = WKG_SITE_URL+'/'+kmlFileName+'.kml?rand='+ (new Date().getTime());
				var georssLayer = new google.maps.KmlLayer( url );
				
				deleteOverlays();

				georssLayer.setMap(gmap);
			}
		});
	});

	function gmap_initialize(){
        var mapOptions = {
			center: new google.maps.LatLng(0, 100),
			zoom: 2,
			scrollwheel: false,
			mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        gmap = new google.maps.Map(document.getElementById("wkg-gmap-canvas"), mapOptions);
    }

    function addMarker(data) {
    	var location = null;

    	if(data.lat && data.lng){
    		location = new google.maps.LatLng(data.lat,data.lng);
    	}
    	
    	if(location != null){
    		marker = new google.maps.Marker({
				position: location,
				map: gmap,
				icon: WKG_ICONS_URL+'/'+data.icon,
				title: data.title
			});

			markersArray.push(marker);
    	}
	}

	// Removes the overlays from the map, but keeps them in the array
	function clearOverlays() {
		if (markersArray) {
			for (i in markersArray) {
				markersArray[i].setMap(null);
	    	}
		}
	}

	// Shows any overlays currently in the array
	function showOverlays() {
		if (markersArray) {
	    	for (i in markersArray) {
	    		markersArray[i].setMap(gmap);
	    	}
		}
	}

	// Deletes all markers in the array by removing references to them
	function deleteOverlays() {
		if (markersArray) {
	    	for (i in markersArray) {
	    		markersArray[i].setMap(null);
	    	}
	    	markersArray.length = 0;
		}
	}

	function geoEncode(address){
		var encoded_address = $.trim(address.replace(/\s+/g, " "));
		var url = 'http://maps.google.com/maps/api/geocode/json?sensor=false&address='+encoded_address;

		$.getJSON(url, function(data){
			if(data.status == "OK"){
				var lat = data.results[0].geometry.location.lat;
				var lng = data.results[0].geometry.location.lng;

				return { lat: lat, lng: lng};
			}

			return null;
		});
	}

})(jQuery);