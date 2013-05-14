google.load('maps', '2');

BlogLocations = Class.create();

BlogLocations.prototype = {

    container    : null, // DOM element in that holds locations
    mapContainer : null, // DOM element that holds the map
    map          : null, // The instance of Google Maps

    markers   : $A([]), // holds all markers added to map
    markerTemplate : new Template('<div>#{desc}</div>'),

    initialize : function(container)
    {
        this.container = $(container);

        if (!this.container)
            return;

        this.mapContainer = this.container.down('.map');

        if (!this.mapContainer)
            return;

        Event.observe(window, 'load', this.loadMap.bind(this));
    },

    loadMap : function()
    {
        if (!google.maps.BrowserIsCompatible())
            return;

        Event.observe(window, 'unload', this.unloadMap.bind(this));

        this.map = new google.maps.Map2(this.container.down('.map'));
        this.zoomAndCenterMap();

        this.map.addControl(new google.maps.MapTypeControl());
        this.map.addControl(new google.maps.ScaleControl());
        this.map.addControl(new google.maps.LargeMapControl());

        this.map.enableDoubleClickZoom();
        this.map.enableContinuousZoom();

        this.container.getElementsBySelector('.geo').each(function(geo) {
            var coords = geo.title.split(';');
            this.addMarkerToMap(
                coords[0],
                coords[1],
                geo.innerHTML
            );
        }.bind(this));

        this.zoomAndCenterMap();
    },

    zoomAndCenterMap : function()
    {
        if (this.markers.size() == 0) {
            this.map.setCenter(new google.maps.LatLng(0, 0),
                               1,
                               G_HYBRID_MAP);

            return;
        }

        var bounds = new google.maps.LatLngBounds();
        this.markers.each(function(marker) {
            bounds.extend(marker.getPoint());
        });
        
        var zoom = Math.max(1, this.map.getBoundsZoomLevel(bounds) - 1);
        this.map.setCenter(bounds.getCenter(), zoom, G_HYBRID_MAP);
    },

    addMarkerToMap : function(lat, lng, desc)
    {
        var marker = new google.maps.Marker(
            new google.maps.LatLng(lat, lng),
            { 'title' : desc }
        );

        var html = this.markerTemplate.evaluate({
            'lat'         : lat,
            'lng'         : lng,
            'desc'        : desc
        });

        marker.bindInfoWindowHtml(html);

        this.map.addOverlay(marker);
        this.markers.push(marker);
    },

    unloadMap : function()
    {
        google.maps.Unload();
    }
};
