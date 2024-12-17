let searchMapSmall;
let searchMapBig;
let drawControl;
let editableLayers;
let nominatim_select = -1;

function applyLocation() {
    let bounds = searchMapBig.getBounds();
    if (editableLayers.getLayers()[0]) {
        bounds = editableLayers.getLayers()[0].getBounds();

    }
    let north = bounds.getNorth().toString();
    north = north.substring(0, north.indexOf('.') + 4);
    let south = bounds.getSouth().toString();
    south = south.substring(0, south.indexOf('.') + 4);

    let east = bounds.getEast();
    let west = bounds.getWest();

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

    const bbox = west + ',' + north + ',' + east + ',' + south;
    const url = new URL(location.href);
    url.searchParams.set("bbox", bbox);

    location.href = url.toString();
}

function nominatimSearch(e, nominatimUrl, isBoundary) {
    let keycode = (e.keyCode ? e.keyCode : e.which);
    if (keycode === 13) {
        if (nominatim_select > -1 && $('#nominatim-result').children().length > 0) {
            const childs = $('#nominatim-result').children().get(0).children
            if (nominatim_select >= 0 && nominatim_select < childs.length) {
                childs[nominatim_select].click();
            }
        } else {
            $.get(nominatimUrl, {q: $('#nominatim-query').val(), format: 'json'}, function (data) {
                let result = '<ul>';

                data.filter(isBoundary).forEach(function (item) {
                    const bounds = '[[' + item['boundingbox'][1] + ', ' + item['boundingbox'][2] + '],[' + item['boundingbox'][0] + ', ' + item['boundingbox'][3] + ']]';
                    result += '<li onClick="f(' + bounds + ');" title="' + item['display_name'] + '">' + item['display_name'];
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
    } else if (keycode === 38 || keycode === 40) {
        if ($('#nominatim-result').children().length > 0) {
            $('#nominatim-result').show();
            const childs = $('#nominatim-result').children().get(0).children

            if (nominatim_select >= 0 && nominatim_select < childs.length) {
                childs[nominatim_select].classList.remove("nominatim-selected");
            }

            if (keycode === 38)
                nominatim_select--;
            else if (keycode === 40)
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

function initSearchMap(epsg, tileLayerUrl, wmsUrl, wmsName, attribution, opacity, nominatimUrl, triggerNominatimOnInput, bbox){
    $('#filter-content-group').show();
    $('#spatial-filter-group').show();
    $('#spatial-content-tab').show();

    $("#spatial-cancel").on('click', function(e){
        closeMapOverlay();
    });

    function closeMapOverlay(){
        $('#nominatim-query').val('');
        $('#nominatim-result').empty();
        document.querySelector("#search-map-overlay").close()
    }

    searchMapSmall = L.map('search-map',{
        epsg: epsg
    }).setView([52, 10], 4);
    searchMapBig = L.map('search-map-big', {
        epsg: epsg
    });

    var bgLayerSmall = null;
    if (wmsUrl && wmsName) {
        bgLayerSmall = L.tileLayer(wmsUrl, {
            layer: wmsName,
            maxZoom: 14,
            attribution: attribution,
            opacity: opacity,
            useCache: true
        });
    } else {
        bgLayerSmall = L.tileLayer(tileLayerUrl, {
            maxZoom: 14,
            attribution: attribution,
            opacity: opacity,
            useCache: true
        });
    }
    bgLayerSmall.addTo(searchMapSmall);
    L.control.zoom(false);
    searchMapSmall.attributionControl.setPrefix('');


    L.Control.Edit = L.Control.extend({
        onAdd: function(map) {
            const span = L.DomUtil.create('span', 'sr-only');

            let a = L.DomUtil.create('a', 'open-map-icon');

            a.title = "Raumbezug definieren"
            if(bbox){
                a.title = "Raumbezug ändern"
            }
            a.appendChild(span);
            a.addEventListener('click', function(e){
                document.querySelector("#search-map-overlay").show();
                searchMapBig.invalidateSize();

                initMap()
            });

            const div = L.DomUtil.create('div',  'leaflet-draw-toolbar leaflet-bar');
            div.appendChild(a);

            if(bbox) {
                a = L.DomUtil.create('a', 'delete-icon');

                a.title = "Raumbezug entfernen"
                a.appendChild(span);
                a.addEventListener('click', function (e) {
                    const url = new URL(location.href);
                    url.searchParams.delete("bbox");
                    location.href = url.toString();
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

    var bgLayerBig = null;
    if (wmsUrl && wmsName) {
        bgLayerBig = L.tileLayer(wmsUrl, {
            layer: wmsName,
            maxZoom: 14,
            attribution: attribution,
            opacity: opacity,
            useCache: true
        });
    } else {
        bgLayerBig = L.tileLayer(tileLayerUrl, {
            maxZoom: 14,
            attribution: attribution,
            opacity: opacity,
            useCache: true
        });
    }
    bgLayerBig.addTo(searchMapBig);
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


    const drawPluginOptions = {
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
        const layer = e.layer;

        editableLayers.clearLayers();
        editableLayers.addLayer(layer);
    });
    searchMapBig.on('draw:deletestart', function() {
        setTimeout(removeSpatial, 10);
    });
    searchMapBig.on('draw:editstart', function() {
        $(".leaflet-draw-actions").hide();
    });
    searchMapBig.on('draw:drawstart', function() {
        $(".leaflet-draw-actions").hide();
    });
    function removeSpatial(){
        editableLayers.clearLayers();
        drawControl._toolbars.edit._modes.remove.handler.disable();
    }

    function isBoundary(item){
        return item['class'] === 'boundary';
    }

    $('#spatial-send').on('click', function(){ applyLocation(); });
    $('#nominatim-query').on('keydown', function(e) { nominatimSearch(e, nominatimUrl, isBoundary); });

    if(triggerNominatimOnInput) {
        $('#nominatim-query').on('input', function () {
            nominatim_select = -1;
            $.get(nominatimUrl, {q: $('#nominatim-query').val(), format: 'json'}, function (data) {
                let result = '<ul>';
                data.filter(isBoundary).forEach(function (item) {
                    const bounds = '[[' + item['boundingbox'][1] + ', ' + item['boundingbox'][2] + '],[' + item['boundingbox'][0] + ', ' + item['boundingbox'][3] + ']]';
                    result += '<li onClick="f(' + bounds + ');" title="' + item['display_name'] + '">' + item['display_name'];
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
    const layer = L.rectangle(bounds, {color: '#3278B9', weight: 1});

    editableLayers.clearLayers();
    editableLayers.addLayer(layer);
    searchMapBig.fitBounds(bounds,{padding: [15,15]});
    nominatim_select = -1;
    $('#nominatim-result').hide();
}
