var map = null;
var x=0;
/**
 * file_name = the base of the filename
 * width/height = w/h of the image
 * url = the url to the tiles directory
 * req = which image to display, corosponds to the open_layers_zoom_total_zooms counter
 */
function open_layers_zoom_add_zoom(file_name_base, width, height, url, req) {

 jQuery("#zoom"+x).append(jQuery("<div>").attr("id", 'open_layers_zoom_map'+x));
 jQuery("#zoom"+x).append(jQuery("<div>").attr("id", 'open_layers_zoom_map_more'+x));
 jQuery("#zoom"+x).append(jQuery("<div>").attr("id", 'open_layers_zoom_map_full_window'+x));




        /* Vector layer */

        /**
         * Layer style
         */
        var vectorLayer = new OpenLayers.Layer.Vector("Simple Geometry", {
            styleMap: new OpenLayers.StyleMap({
              "default": new OpenLayers.Style({
                fillColor: "red",
                fillOpacity: 0,
                strokeColor: "red",
                strokeWidth: 0
              }),
              "highlight": new OpenLayers.Style({
                fillColor: "red",
                fillOpacity: 0.2,
                strokeWidth: 0
              })
            })
        });

        zoomify = new OpenLayers.Layer.Zoomify( "zoom", url, new OpenLayers.Size( width, height ) );

        var mapbounds =  new OpenLayers.Bounds(0, 0, width, height);



        // We must list all the controls, since we want to replace the default
        // PanZoom with a PanZoomBar
        options = {
            maxExtent: mapbounds,
            restrictedExtent: mapbounds,
            maxResolution: Math.pow(2, zoomify.numberOfTiers -1),
            numZoomLevels: zoomify.numberOfTiers,
            units: "pixels",

        };

        map = new OpenLayers.Map("open_layers_zoom_map"+x, options);
        map.addLayer(zoomify);
        map.setBaseLayer(zoomify);

        if (!map.getCenter()) map.zoomTo(2);

        // Add overview map
        // workaround based on http://osgeo-org.1803224.n2.nabble.com/zoomify-layer-WITH-overview-map-td5534360.html

        // Optional number to reduce your original pixel to fit Overview map
        // container (I used Math.floor(width/150), since my container is
        // 150 x 110)
        var ll = Math.floor(width/150);
        var a = width/ll;
        var b = height/ll;

        //New layer and new control:
        var overview = new OpenLayers.Layer.Image(
            'overview',
            url + 'TileGroup0/0-0-0.jpg',
            mapbounds,
            new OpenLayers.Size(a, b), {
                numZoomLevels: 1,
                maxExtent: mapbounds,
                restrictedExtent: mapbounds
            }
        );
        var overviewVectors = vectorLayer.clone();
        var overviewControl = new OpenLayers.Control.OverviewMap({
            // This is optional,you may use default values.
            size: new OpenLayers.Size(150, Math.floor(b)),
            autopan: false,
            maximized: true,
            layers: [overview, overviewVectors]
        });

        // At last,adding it to the map:
        map.addControl(overviewControl);

        x = ++x;
}
