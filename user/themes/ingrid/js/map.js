
var searchMapSmall;
var searchMapBig;
var drawControl;
var editableLayers;


function applyLocation() {
    var bounds = searchMapBig.getBounds();
    if (editableLayers.getLayers()[0]) {
        bounds = editableLayers.getLayers()[0].getBounds();

    }
    var north = bounds.getNorth().toString();
    north = north.substring(0, north.indexOf('.') + 4);
    var south = bounds.getSouth().toString();
    south = south.substring(0, south.indexOf('.') + 4);

    var east = bounds.getEast();
    var west = bounds.getWest();

    if (east - west >= 360) {
        east = 180.0;
        west = -180.0;
    }

    while (east < -180) {
        east = east + 360
    }
    while (east > 180) {
        east = east - 360
    }
    east = east.toString();
    if (east.indexOf('.') > -1)
        east = east.substring(0, east.indexOf('.') + 4);

    while (west < -180) {
        west = west + 360
    }
    while (west > 180) {
        west = west - 360
    }
    west = west.toString();
    if (west.indexOf('.') > -1)
        west = west.substring(0, west.indexOf('.') + 4);

    var bbox = west + ',' + north + ',' + east + ',' + south;
    var url = new URL(location.href);
    url.searchParams.set("bbox", bbox);

    location.href = url.toString();
}

function nominatimSearch(e, nominatim_select, nominatimUrl, isBoundary) {
    var keycode = (e.keyCode ? e.keyCode : e.which);
    if (keycode == '13') {
        if (nominatim_select > -1 && $('#nominatim-result').children().length > 0) {
            var childs = $('#nominatim-result').children().get(0).children
            if (nominatim_select >= 0 && nominatim_select < childs.length) {
                childs[nominatim_select].click();
            }
        } else {
            $.get(nominatimUrl, {q: $('#nominatim-query').val(), format: 'json'}, function (data) {
                var result = '<ul>';

                data.filter(isBoundary).forEach(function (item) {
                    var bounds = '[[' + item['boundingbox'][1] + ', ' + item['boundingbox'][2] + '],[' + item['boundingbox'][0] + ', ' + item['boundingbox'][3] + ']]';
                    result += '<li onClick="f(' + bounds + ');nominatim_select = -1;$(\'#nominatim-result\').hide();" title="' + item['display_name'] + '">' + item['display_name'];
                });
                result += '</ul>';
                $('#nominatim-result').empty();
                $('#nominatim-result').append(result);
                if (data.filter(isBoundary).length > 0)
                    $('#nominatim-result').show();
                else
                    $('#nominatim-result').hide();
            });
            nominatim_select = -1;
        }
    } else if (keycode == '38' || keycode == '40') {
        if ($('#nominatim-result').children().length > 0) {
            $('#nominatim-result').show();
            var childs = $('#nominatim-result').children().get(0).children

            if (nominatim_select >= 0 && nominatim_select < childs.length) {
                childs[nominatim_select].classList.remove("nominatim-selected");
            }

            if (keycode == '38')
                nominatim_select--;
            else if (keycode == '40')
                nominatim_select++;

            if (nominatim_select >= childs.length)
                nominatim_select = -1;
            if (nominatim_select < -1)
                nominatim_select = childs.length - 1;

            if (nominatim_select >= 0 && nominatim_select < childs.length) {
                childs[nominatim_select].classList.add("nominatim-selected");
            }

        }
    } else {
        nominatim_select = -1;
    }
}

function initSearchMap(tileLayerUrl, nominatimUrl, triggerNominatimOnInput, bbox){
    $('#filter-content-group').show();
    $('#spatial-filter-group').show();
    $('#spatial-content-tab').show();

    $("#search-map-overlay").on('click', function(e){
        closeMapOverlay();
    });
    $("#spatial-cancel").on('click', function(e){
        closeMapOverlay();
    });
    $("#search-map-row").on('click', function(e){
        e.stopPropagation();
    });
    function closeMapOverlay(){
        $('#search-map-overlay').hide()
        $('#nominatim-query').val('');
        $('#nominatim-result').empty();
        $('#nominatim-result').hide();
    }

    searchMapSmall = L.map('search-map').setView([52, 10], 4);
    searchMapBig = L.map('search-map-big');

    L.tileLayer(tileLayerUrl, {
        maxZoom: 14,
        attribution: 'Kartendaten &copy; <a href="http://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> Mitwirkende',
        useCache: true
    }).addTo(searchMapSmall);
    L.control.zoom(false);
    searchMapSmall.attributionControl.setPrefix('');


    L.Control.Edit = L.Control.extend({
        onAdd: function(map) {
            var span = L.DomUtil.create('span', 'sr-only');

            var a = L.DomUtil.create('a', 'open-map-icon');

            a.title = "Raumbezug definieren"
            if(bbox){
                a.title = "Raumbezug ändern"
            }
            a.appendChild(span);
            a.addEventListener('click', function(e){$('#search-map-overlay').show();
                searchMapBig.invalidateSize();

                initMap()});

            var div = L.DomUtil.create('div',  'leaflet-draw-toolbar leaflet-bar');
            div.appendChild(a);

            if(bbox) {
                a = L.DomUtil.create('a', 'delete-icon');

                a.title = "Raumbezug entfernen"
                a.appendChild(span);
                a.addEventListener('click', function (e) {
                    location.href = resetUrl;
                });

                div.appendChild(a);
            }

            return div;
        },

        onRemove: function(map) {
            // Nothing to do here
        }
    });

    L.control.edit = function(opts) {
        return new L.Control.Edit(opts);
    }

    L.control.edit({ position: 'topright' }).addTo(searchMapSmall);

    if(bbox){
        L.rectangle(bbox, {color: '#3278B9', weight: 1}).addTo(searchMapSmall);
        searchMapSmall.fitBounds(bbox,{padding: [5,5]});
    }

    $('#filter-content-group').hide();

    L.tileLayer(tileLayerUrl, {
        maxZoom: 14,
        attribution: 'Kartendaten &copy; <a href="http://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> Mitwirkende',
        useCache: true
    }).addTo(searchMapBig);
    L.control.zoom(false);
    searchMapBig.attributionControl.setPrefix('');

    // Initialise the FeatureGroup to store editable layers
    editableLayers = new L.FeatureGroup();
    searchMapBig.addLayer(editableLayers);

    function initMap(){
        editableLayers.clearLayers()

        if(!bbox){
            searchMapBig.setView([52, 10],5);
        }
        else {
            L.rectangle(bbox, {color: '#3278B9', weight: 1}).addTo(editableLayers);
            searchMapBig.fitBounds(bbox, {padding: [15, 15]});
        }
    }

    initMap();


    var drawPluginOptions = {
        position: 'topright',
        draw: {
            polygon: false,
            polyline: false,
            rectangle: {
                shapeOptions: {
                    clickable: false,
                    color: '#3278B9'
                },
                showArea: false
            },
            circle: false,
            marker: false,
            circlemarker: false
        },
        edit: {
            featureGroup: editableLayers,
            edit: {
                selectedPathOptions:{
                    fillColor: '#3278B9',
                    fillOpacity: 0.2,
                }
            },
            remove: {}
        }
    };

    L.drawLocal = {
        draw: {
            toolbar: {
                actions: {
                    title: 'Abbrechen',
                    text: 'Abbrechen'
                },
                finish: {
                    title: 'Beenden',
                    text: 'Beenden'
                },
                undo: {
                    title: 'Rückgängig',
                    text: 'Rückgängig'
                },
                buttons: {
                    rectangle: 'Raumbezug erstellen',
                }
            },
            handlers: {
                simpleshape:  {tooltip: {}},
                polygon: false,
                polyline: false,
                rectangle: {
                    tooltip: {
                        start: 'Klicken und ziehen.',
                    }
                },
                circle: {tooltip: {}},
                marker: {tooltip: {}},
                circlemarker: {tooltip: {}},
            }
        },
        edit: {
            toolbar: {
                actions: {
                    save: {
                        title: 'Übernehmen',
                        text: 'Übernehmen'
                    },
                    cancel: {
                        title: 'Abbrechen',
                        text: 'Abbrechen'
                    },
                    clearAll: {
                        title: 'Entfernen',
                        text: 'Entfernen'
                    }
                },
                buttons: {
                    edit: 'Raumbezug bearbeiten',
                    editDisabled: 'Kein Raumbezug zum ändern vorhanden',
                    remove: 'Raumbezug entfernen',
                    removeDisabled: 'Kein Raumbezug zum ändern vorhanden'
                }
            },
            handlers: {
                edit: {
                    tooltip: {
                        text: 'Raumbezug verändern über Eckpunkte oder verschieben über Mittelpunkt',
                        subtext: ''
                    }
                },
                remove: {
                    tooltip: {
                        text: 'Raumbezug anklicken zum entfernen.'
                    }
                },
            }
        }
    };

    // Initialise the draw control and pass it the FeatureGroup of editable layers
    drawControl = new L.Control.Draw(drawPluginOptions);
    searchMapBig.addControl(drawControl);

    searchMapBig.on('draw:created', function(e) {
        var layer = e.layer;

        editableLayers.clearLayers();
        editableLayers.addLayer(layer);
    });
    searchMapBig.on('draw:deletestart', function(e) {
        setTimeout(removeSpatial, 10);
    });
    searchMapBig.on('draw:editstart', function(e) {
        $(".leaflet-draw-actions").hide();
    });
    searchMapBig.on('draw:drawstart', function(e) {
        $(".leaflet-draw-actions").hide();
    });
    function removeSpatial(){
        editableLayers.clearLayers();
        drawControl._toolbars.edit._modes.remove.handler.disable();
    }

    function isBoundary(item){
        return item['class'] === 'boundary';
    }

    var nominatim_select = -1;

    $('#spatial-send').on('click', function(){ applyLocation(); });
    $('#nominatim-query').on('keydown', function(e){ nominatimSearch(e, nominatim_select, nominatimUrl, isBoundary); });

    if(triggerNominatimOnInput) {
        $('#nominatim-query').on('input', function (e) {
            nominatim_select = -1;
            $.get(nominatimUrl, {q: $('#nominatim-query').val(), format: 'json'}, function (data) {
                var result = '<ul>';
                data.filter(isBoundary).forEach(function (item) {
                    var bounds = '[[' + item['boundingbox'][1] + ', ' + item['boundingbox'][2] + '],[' + item['boundingbox'][0] + ', ' + item['boundingbox'][3] + ']]';
                    result += '<li onClick="f(' + bounds + ');nominatim_select = -1;$(\'#nominatim-result\').hide();" title="' + item['display_name'] + '">' + item['display_name'];
                });
                result += '</ul>';
                $('#nominatim-result').empty();
                $('#nominatim-result').append(result);
                if (data.filter(isBoundary).length > 0)
                    $('#nominatim-result').show();
                else
                    $('#nominatim-result').hide();
            });
        });
    }
}

function f(bounds){
    var layer = L.rectangle(bounds, {color: '#3278B9', weight: 1});

    editableLayers.clearLayers();
    editableLayers.addLayer(layer);
    searchMapBig.fitBounds(bounds,{padding: [15,15]});
}

function initResultMap(id, tileLayerUrl, dragAndZoom, geoJSON, spatialText){

    var mapOptions = {};
    if(!dragAndZoom){
        mapOptions = {
            zoomControl: false,
            scrollWheelZoom: false,
            dragging: false}
    }

    var map = L.map('mapid-'+id, mapOptions).setView([52, 10], 4);


    L.tileLayer(tileLayerUrl, {
        maxZoom: 18,
        attribution: 'Kartendaten &copy; <a href="http://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> Mitwirkende',
        useCache: true
    }).addTo(map);
    L.control.zoom(false);
    map.attributionControl.setPrefix('');


    var allBounds = createSpatialLayer(map, geoJSON, spatialText);
    map.invalidateSize();
    map.fitBounds(allBounds,{padding: [5,5]});
}

function createSpatialLayer(map, geoJSON, spatialText){
    var type = geoJSON['type'].toLowerCase();

    var allBounds = [];

    if (type === 'geometrycollection'){
        var subgeometries = geoJSON['geometries'];
        for(var i=0; i < subgeometries.length; i++){
            allBounds.push(createSpatialLayer(map, subgeometries[i], spatialText));
        }
    }
    else if(type === 'envelope'){
        var bounds = geoJSON['coordinates'];
        allBounds.push(bounds);
        var layer = L.rectangle(bounds, {color: '#3278B9', weight: 1});
        if(spatialText != null && spatialText.trim() !== '')
            layer.bindTooltip(spatialText, {direction: 'center'});
        layer.addTo(map);
    }
    else if(type === 'linestring' || type === 'multilinestring'){
        var bounds = geoJSON['coordinates'];
        allBounds.push(bounds);
        var layer = L.polyline(bounds, {color: '#3278B9', weight: 1});
        if(spatialText != null && spatialText.trim() !== '')
            layer.bindTooltip(spatialText, {direction: 'center'});
        layer.addTo(map);
    }
    else if(type === 'point' || type === 'multipoint'){
        for(var i = 0; i < geoJSON['coordinates'].length; i++) {
            for(var j = 0; j < geoJSON['coordinates'][i].length; j++) {
                var point = geoJSON['coordinates'][i][j];
                var bounds = [[(point[0] + 0.2),(point[1] + 0.2)],
                    [(point[0] - 0.2), (point[1] - 0.2) ]];
                allBounds.push(bounds);
                var layer = L.marker(point, {color: '#3278B9', weight: 1});
                if(spatialText != null && spatialText.trim() !== '')
                    layer.bindTooltip(spatialText, {direction: 'center'});
                layer.addTo(map);
            }
        }
    }
    else {
        var bounds = geoJSON['coordinates'];
        allBounds.push(bounds);
        var layer = L.polygon(bounds, {color: '#3278B9', weight: 1});
        if(spatialText != null && spatialText.trim() !== '')
            layer.bindTooltip(spatialText, {direction: 'center'});
        layer.addTo(map);
    }
    return allBounds;
}
