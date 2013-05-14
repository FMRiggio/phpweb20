google.load('maps', '2');

BlogLocationManager = Class.create();

BlogLocationManager.prototype = {
	url			:	null,
	
	id_post		:	null,
	container	:	null,
	map			:	null,
	geocoder	:	null,
	
	markers		:	$H({}),
	
	markerTemplate	:	new Template(
		'<div>' 
	  + '	#{desc}<br />'
	  + '	<input type="button" value="Rimuovi luogo" />'
	  + '</div>'
	),
	
	initialize	:	function(container, form)
	{
		form			= $(form);
		this.url		= form.action;
		this.id_post	= $F(form.id_post);
		this.container	= $(container);
		
		this.geocoder	= new google.maps.ClientGeocoder();
		
		Event.observe(window, 'load', this.loadMap.bind(this));
		form.observe('submit', this.onFormSubmit.bindAsEventListener(this));
	},
	
	loadMap		:	function()
	{
		if (!google.maps.BrowserIsCompatible()) {
			return;
		}
			
		Event.observe(window, 'unload', this.unloadMap.bind(this));
		
		this.map = new google.maps.Map2(this.container);
		this.zoomAndCenterMap();
				
		this.map.addControl(new google.maps.MapTypeControl());
		this.map.addControl(new google.maps.ScaleControl());
		this.map.addControl(new google.maps.LargeMapControl());
		
		var overviewMap = new google.maps.OverviewMapControl();
		this.map.addControl(overviewMap);
		overviewMap.hide(true);
		
		this.map.enableDoubleClickZoom();
		this.map.enableContinuousZoom();
		
		var options = {
			parameters	:	'action=get&id_post=' + this.id_post,
			onSuccess	:	this.loadLocationSuccess.bind(this)
		}
		
		new Ajax.Request(this.url, options);
	},
	
	zoomAndCenterMap	:	function()
	{
		var bounds = new google.maps.LatLngBounds();
		this.markers.each(function(pair) {
			bounds.extend(pair.value.getPoint());
		});
		
		if (bounds.isEmpty()) {
			this.map.setCenter(new google.maps.LatLng(0, 0), 1, G_HYBRID_MAP);
		} else {
			var zoom = Math.max(1, this.map.getBoundsZoomLevel(bounds) - 1);
			this.map.setCenter(bounds.getCenter(), zoom);
		}
	},
	
	addMarkerToMap	:	function(id, lat, lng, desc)
	{
		this.removeMarkerFromMap(id);
		
		this.markers[id] = new google.maps.Marker(
			new google.maps.LatLng(lat, lng), 
			{ 'title' : desc, draggable	: true}
		);
		
		var that = this;
		google.maps.Event.addListener(this.markers[id], 'dragend', function(){
			that.dragComplete(this);
		});
		google.maps.Event.addListener(this.markers[id], 'dragstart', function(){
			that.closeInfoWindow();
		});		
		
		this.map.addOverlay(this.markers[id]);
		
		var html = this.markerTemplate.evaluate({
			'id_luogo'	:	id,
			'lat'		:	lat,
			'lng'		:	lng,
			'desc'		:	desc
		});
		
		var node = Builder.build(html);
		var button = node.getElementsBySelector('input')[0];
		
		button.setAttribute('id_luogo', id);
		
		button.observe('click', this.onRemoveMarker.bindAsEventListener(this));
		
		this.markers[id].bindInfoWindow(node);
		
		return this.markers[id];
	},
	
	removeMarkerFromMap	:	function(id_luogo)
	{
		if (!this.hasMarker(id_luogo))
			return;
		this.map.removeOverlay(this.markers[id_luogo]);
		this.markers.remove(id_luogo);
	},
	
	hasMarker	:	function(id_luogo)
	{
		var id_luoghi = this.markers.keys();
		
		return id_luoghi.indexOf(id_luogo) >= 0;
	},
	
	loadLocationSuccess	:	function(transport)
	{
		var json = transport.responseText.evalJSON(true);
		
		if (json.locations == null)
			return;
		
		json.locations.each(function(location){
			this.addMarkerToMap(
				location.id_luogo,
				location.latitudine,
				location.longitudine,
				location.descrizione
			);
		}.bind(this));
		
		this.zoomAndCenterMap();
	},
	
	onFormSubmit	:	function(e)
	{
		Event.stop(e);
		
		var form = Event.element(e);
		var address = $F(form.location).strip();
		
		if (address.length == 0)
			return;
		
		this.geocoder.getLocations(address, this.createPoint.bind(this));
	},
	
	
	createPoint		:	function(locations)
	{
		if (locations.Status.code != G_GEO_SUCCESS) {
			//c'è stato un errore
			var msg = '';
			switch (locations.Status.code) {
				case G_GEO_BAD_REQUEST:
					msg = 'Impossibile analizzare la richiesta';
					break;
				case G_GEO_MISSING_QUERY:
					msg = 'Query non specificata';
					break;
				case G_GEO_UNKNOW_ADDRESS:
					msg = 'Impossibile trovare l\'indirizzo';
					break;
				case G_GEO_UNAVAILABLE_ADDRESS:
					msg = 'Indirizzo vietato';
					break;
				case G_GEO_BAD_KEY:
					msg = 'Chiave API non valida';
					break;
				case G_GEO_TOO_MANY_QUERIES:
					msg = 'Troppe query di geocodifica';
					break;
				case G_GEO_SERVER_ERROR:
				default:
					msg = 'Si è verificato un errore sconosciuto del server';
					break;
			}
			
			message_write(msg);
			return;
		}
		
		var placemark = locations.Placemark[0];
		
		var options = {
			parameters	:	'action=add'
						+   '&id_post=' 	+ this.id_post
						+   '&descrizione=' + escape(placemark.address)
						+	'&latitudine='	+ placemark.Point.coordinates[1]
						+	'&longitudine=' + placemark.Point.coordinates[0],
			onSuccess	:	this.createPointSuccess.bind(this)
		}
		
		new Ajax.Request(this.url, options);
	},
	
	
	createPointSuccess	:	function(transport)
	{
		var json = transport.responseText.evalJSON(true);
		
		if (json.id_luogo == 0) {
			message_write('Errore nell\'aggiunta del luogo al post del blog');
			return;
		}
		
		marker = this.addMarkerToMap(json.id_luogo, json.latitudine, json.longitudine, json.descrizione);
		
		google.maps.Event.trigger(marker, 'click');
		
		this.zoomAndCenterMap();
	},
	
	
	dragComplete	:	function(marker)
	{
		var point = marker.getPoint();
		var options = {
			parameters	:	'action=move'
						+   '&id_post=' 	+ this.id_post
						+   '&id_luogo=' + marker.id_luogo
						+	'&latitudine='	+ point.lat()
						+	'&longitudine=' + point.lng(),
			onSuccess	:	this.onDragCompleteSuccess.bind(this)
		}
		
		new Ajax.Request(this.url, options);
	},
	
	
	onDragCompleteSuccess	:	function(transport)
	{
		var json = transport.responseText.evalJSON(true);
		
		if (json.id_luogo && this.hasMarker(json.id_luogo)) {
			var point = new google.maps.LatLng(json.latitudine, json.longitudine);
			var marker = this.addMarkerToMap(json.id_luogo, json.latitudine, json.longitudine, json.descrizione);
			google.maps.Event.trigger(marker, 'click');
		}
	},
	
	
	onRemoveMarker	:	function(e)
	{
		var button = Event.element(e);
		var id_luogo = button.getAttribute('id_luogo');
		var options = {
				parameters	:	'action=delete'
							+   '&id_post=' 	+ this.id_post
							+   '&id_luogo=' + id_luogo,
				onSuccess	:	this.onRemoveMarkerSuccess.bind(this)
			}
			
		new Ajax.Request(this.url, options);		
	},
	
	
	onRemoveMarkerSuccess	:	function(transport)
	{
		var json = transport.responseText.evalJSON(true);
		
		if (json.id_luogo) {
			this.removeMarkerFromMap(json.id_luogo);
		}
	},
	
	
	unloadMap	:	function()
	{
		google.maps.Unload();
	}
		
};