<?php
wp_enqueue_script( 'googlemaps' );
wp_enqueue_script( 'pods-maps' );
wp_enqueue_style( 'pods-maps' );

$attributes = array();
$attributes = PodsForm::merge_attributes( $attributes, $name, '', $options );

$map_options = array();
if ( ! empty( $options[ 'maps_zoom' ] ) ) {
	$map_options['zoom'] = (int) $options[ 'maps_zoom' ];
} else {
	$map_options['zoom'] = (int) Pods_Component_Maps::$options['map_zoom'];
}
if ( ! empty( $options[ 'maps_type' ] ) ) {
	$map_options['type'] = $options[ 'maps_type' ];
} else {
	$map_options['type'] = Pods_Component_Maps::$options['map_type'];
}
if ( ! empty( $options[ 'maps_marker' ] ) ) {
	$map_options['marker'] = $options[ 'maps_marker' ];
} else {
	$map_options['marker'] = Pods_Component_Maps::$options['map_marker'];
}

if ( ! isset( $address_html ) ) {
	// @todo Check field type
	$format = PodsForm::field_method( 'address', 'default_display_format' );
	if ( $options['address_display_type'] == 'custom' ) {
		$format = $options['address_display_type_custom'];
	}
	$address_html = PodsForm::field_method( 'address', 'format_to_html', $format, $value, $options );
}
$value['address_html'] = $address_html;

?>
<div id="<?php echo $attributes['id'] . '-map-canvas' ?>" class="pods-address-maps-map-canvas" data-value='<?php echo json_encode( $value ) ?>'></div>

<script type="text/javascript">
	jQuery( document ).ready( function ( $ ) {
		var mapCanvas = document.getElementById( '<?php echo $attributes['id'] . '-map-canvas' ?>' ),
			value = $( '#<?php echo $attributes['id'] . '-map-canvas' ?>' ).attr('data-value'),
			latlng = null,
			mapOptions = {
				center: new google.maps.LatLng( 41.850033, -87.6500523 ), // default (Chicago)
				marker: '<?php echo $map_options['marker'] ?>',
				zoom: <?php echo $map_options['zoom'] ?>,
				type: '<?php echo $map_options['type'] ?>'
			};

		if ( value ) {
			try {
				value = JSON.parse( value );
			} catch ( err ) {
				return;
			}
		} else {
			return;
		}

		//------------------------------------------------------------------------
		// Initialze the map
		//
		if ( value.hasOwnProperty('geo') ) {
			latlng = value.geo;
			mapOptions.center = new google.maps.LatLng( latlng );
		}

		var map = new google.maps.Map( mapCanvas, mapOptions );
		var geocoder = new google.maps.Geocoder();

		//------------------------------------------------------------------------
		// Initialze marker
		//
		var markerOptions = {
			map : map,
			position: latlng,
			draggable: false
		};
		var marker = new google.maps.Marker( markerOptions );
		map.setCenter( mapOptions.center );

		//------------------------------------------------------------------------
		// Initialze info window
		//
		var infowindowContent = value.address_html;
		if ( value.info_window ) {
			infowindowContent = podsFormatFieldsToHTML( value.info_window, value.address );
		}
		var infowindow = new google.maps.InfoWindow();

		infowindow.setContent( infowindowContent );
		infowindow.open( map, marker );

		//------------------------------------------------------------------------
		// Helpers
		//
		function podsFormatFieldsToHTML( html, fields ) {
			// Convert magic tags to field values or remove them
			$.each( fields, function( key, field ) {
				if ( field.length ) {
					html = html.replace( '{{' + key + '}}', field );
				} else {
					// Replace with {{PODS}} so we can remove this line if needed
					html = html.replace( '{{' + key + '}}', '{{REMOVE}}' );
				}
			} );
			// Remove empty lines
			var lines = html.split( '<br>' );
			$.each( lines, function( key, line ) {
				if ( line === '{{REMOVE}}' ) {
					// Delete the key it this line only has {{REMOVE}}
					delete lines[ key ];
				} else {
					// Remove {{REMOVE}}
					lines[ key ] = line.replace('{{REMOVE}}', '')
				}
			} );
			// Reset array keys and join it back together
			html = lines.filter(function(){return true;}).join( '<br>' );
			return html;
		}

	} ); // end document ready
</script>