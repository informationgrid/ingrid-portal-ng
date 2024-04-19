/*
 * **************************************************-
 * Ingrid Portal Base
 * ==================================================
 * Copyright (C) 2014 - 2024 wemove digital solutions GmbH
 * ==================================================
 * Licensed under the EUPL, Version 1.2 or – as soon they will be
 * approved by the European Commission - subsequent versions of the
 * EUPL (the "Licence");
 * 
 * You may not use this work except in compliance with the Licence.
 * You may obtain a copy of the Licence at:
 * 
 * https://joinup.ec.europa.eu/software/page/eupl
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the Licence is distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the Licence for the specific language governing permissions and
 * limitations under the Licence.
 * **************************************************#
 */
function ingrid_openWindow(url, winWidth, winHeight)
{
  popupWin = window.open(url, "InternalWin", 'width=' + winWidth + ',height=' + winHeight + ',resizable=yes,scrollbars=yes,location=no,toolbar=yes');
  popupWin.focus();
}

function copyToClipboard(message) {
  var $body = document.getElementsByTagName('body')[0];
  var $tempInput = document.createElement('INPUT');
  $body.appendChild($tempInput);
  $tempInput.setAttribute('value', message)
  $tempInput.select();
  document.execCommand('copy');
  $body.removeChild($tempInput);
}

function ingrid_checkAll(group) {
  // NOTICE: first field in group has to be "checkAll" field
  if (group[0]) {
      if (group[0].checked == true) {
          for (i=1; i<group.length; i++) {
              group[i].checked = true;
          }
      } else {
          for (i=1; i<group.length; i++) {
              group[i].checked = false;
          }
      }
  }
}

function ingrid_enableButtonByCheckbox(id, buttonName){
    var checkboxes = document.getElementsByName(id);
    for (var i=0; i<checkboxes.length; i++){
        //bind event to each checkbox
        checkboxes[i].onclick = function() {
            var isCheckboxSelect = false;
            for (var j=0; j<checkboxes.length; j++){
                if(checkboxes[j].checked){
                    isCheckboxSelect = true;
                }
            }
            document.getElementsByName(buttonName)[0].disabled = !isCheckboxSelect;
        };
    }
}

function ingrid_disableElementByCheckbox(checkBoxName, elementName){
  var checkboxes = document.getElementsByName(checkBoxName);
  for (var i=0; i<checkboxes.length; i++){
        var isCheckboxSelect = false;
        for (var j=0; j<checkboxes.length; j++){
            if(checkboxes[j].checked){
                isCheckboxSelect = true;
            }
        }
        document.getElementsByName(elementName)[0].disabled = isCheckboxSelect;
  }
}

// Select all or nothing in group1 and force group2 to same selection state.
function ingrid_checkAll2Groups(group1, group2) {
    group2[0].checked = group1[0].checked;
    ingrid_checkAll(group1);
    ingrid_checkAll(group2);
}

//Select all or nothing in group1 and adapt only "all field" in group2.
function ingrid_checkAllAdapt(group1, group2) {
    ingrid_checkAll(group1);
    if (group1[0].checked == false) {
        group2[0].checked = false;
    } else {
        ingrid_checkGroup(group2);
    }
}

function ingrid_checkGroup(group) {
    // NOTICE: first field in group has to be "checkAll" field
    var allChecked = true;
    for (i=1; i<group.length; i++) {
        if (group[i].checked != true) {
            allChecked = false;
            break;
        }
    }
    if (allChecked) {
        group[0].checked = true;
    } else {
        group[0].checked = false;
    }
}

//Check for selection of all field in group1 and then adapt group 2.
function ingrid_checkGroupAdapt(group1, group2) {
    ingrid_checkGroup(group1);
    if (group1[0].checked == false) {
        group2[0].checked = false;
    } else {
        ingrid_checkGroup(group2);
    }
}

function login() {
    if (document.getElementById("login").value == "admin") {
        document.location.href="mpu_admin.html";
    } else if (document.getElementById("passwd").value != "") {
        document.location.href="mpu_home.html";
    }
}

function clearUser() {
    document.getElementById("login").value = "";
}

function clearPasswd() {
    document.getElementById("passwd").value = "";
}


function adaptProviderNodes(partnerElementName, formName) {
  var partnerIdent = document.forms[formName].elements[partnerElementName].id;
  if (partnerIdent == "bund") {
    partnerIdent = "bu";
  }
  var checked = document.forms[formName].elements[partnerElementName].checked;
  for (i=0; i<document.forms[formName].elements.length; i++) {
    if (document.forms[formName].elements[i].id.indexOf(partnerIdent+'_') == 0) {
      document.forms[formName].elements[i].checked = checked;
    }
  }
}

function adaptPartnerNode(providerElementName, formName) {
  var partnerIdent = document.forms[formName].elements[providerElementName].id;
  partnerIdent = partnerIdent.substring(0, partnerIdent.indexOf('_'));
  var checked = document.forms[formName].elements[providerElementName].checked;
  var checkPartner = false;
  if (!checked) {
      for (i=0; i<document.forms[formName].elements.length; i++) {
        if (document.forms[formName].elements[i].id.indexOf(partnerIdent+'_') == 0) {
          if (document.forms[formName].elements[i].checked) {
            checkPartner = true;
            break;
          }
        }
      }
  } else {
      checkPartner = true;
  }
  if (partnerIdent == "bu") {
    partnerIdent = "bund";
  }
  document.forms[formName].elements["chk_"+partnerIdent].checked = checkPartner;
}

/* Open and close categories */
function openNode(element){
    var status = document.getElementById(element).style.display;
    var status_img = document.getElementById(element + "_img").src;

    document.getElementById(element).style.display = 'none';
    if(status == "none"){
        document.getElementById(element).style.display = "block";
    }

    document.getElementById(element + "_img").src = '/ingrid-portal-apps/images/facete/facete_cat_close.png';
    if(status_img.indexOf('/ingrid-portal-apps/images/facete/facete_cat_close.png') != -1){
        document.getElementById(element + "_img").src = '/ingrid-portal-apps/images/facete/facete_cat_open.png';
    }

}

/* Open and close facete */
function openFaceteNode(element, id_facete, id_hits){
    var status_img = document.getElementById(element + "_img").src;

    if(status_img.indexOf('/ingrid-portal-apps/images/facete/facete_close.png') > -1){
        document.getElementById(element).style.display = "block";
        document.getElementById(element + "_img").src = '/ingrid-portal-apps/images/facete/facete_open.png';
        document.getElementById(id_hits).setAttribute('class', "closeNode");
        document.getElementById(id_facete).setAttribute('class', "openNode");
    }else{
        document.getElementById(element).style.display = 'none';
        document.getElementById(element + "_img").src = '/ingrid-portal-apps/images/facete/facete_close.png';
        document.getElementById(id_hits).setAttribute('class', "openNode");
        document.getElementById(id_facete).setAttribute('class', "closeNode");
    }

}

/* select all checkboxes in form */
function faceteDialogSelectAll(field){
    for (i = 0; i < field.length; i++)
        field[i].checked = true ;
}

/* deselect all checkboxes in form */
function faceteDialogDeselectAll(field){
    for (i = 0; i < field.length; i++)
        field[i].checked = false ;
}

/* cancel dialog */
function faceteDialogCancel(id){
    var dialog = dijit.byId(id);
    dialog.hide();
}

/* open dialog by onclick-event */
function prepareDialog (id) {
   var dialog = dijit.byId(id);
   dialog.show();
}

/* open dialog for map */
function prepareDialogMap (id, wms, divId, iframeId) {
        var dialog = dijit.byId(id);
        var map = document.getElementById(divId);
        //render iFrame, but only if doest exist already
        if (map.childNodes.length <= 1 && dojo.byId(iframeId) == null) {
            var iframe = document.createElement('iframe');
            iframe.setAttribute('id', iframeId);
            iframe.setAttribute('class', 'facete_map');
            iframe.setAttribute('name', 'ingrid-webmap-client');
            iframe.setAttribute('marginheight', '0');
            iframe.setAttribute('marginwidth', '0');
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('height', '505');
            iframe.setAttribute('width', '805');
            iframe.setAttribute('border', 'none');
            iframe.setAttribute('src', wms);
            map.appendChild(iframe);
        }

        dialog.show();

}

/* open dialog */
function loadDialog(id){
    var dialog = id;
    dialog.show();
}

function loadingProgressDialog(element){
    var status = document.getElementById(element).style.display;
    if(status == "inline"){
        document.getElementById(element).style.display = "none";
    }
    if(status == "none"){
        document.getElementById(element).style.display = "inline";
    }
}

function showButtonSelectCheckboxForm (form, button, coordDiv){
    var status = document.getElementById(button).style.display;
    var isSelect = false
    var divStatus = document.getElementById(coordDiv).firstChild;
    for (i = 0; i < form.length; i++){
        if(form[i].checked){
            isSelect = true;
            break;
        }
    }

    if(isSelect && divStatus != null){
        if(status == "none"){
            document.getElementById(button).style.display = "inline";
        }
    }else{
        if(status == "inline"){
            document.getElementById(button).style.display = "none";
        }
    }

}

function getAndSetMultiple(ob){
    var arraySelect = new Array();
    while (ob.selectedIndex != -1)
    {
        if (ob.selectedIndex != 0) arraySelect.push(ob.selectedIndex);
        ob.options[ob.selectedIndex].selected = false;
    }
    if(arraySelect.length == 0){
        arraySelect.push(0);
    }
    setMultiple(ob, arraySelect)
}

function setMultiple(ob, arraySelect){
    for (var i = 0;i < arraySelect.length; i++)
    {
        ob.options[arraySelect[i]].selected = true;
    }
}

function goToByScroll(id, time){
    if(time == undefined){
        time = 1200;
    }
    id = id.replace("link", "");
    $('html,body').animate({
        scrollTop: $("#"+id).offset().top
    }, time);
}

function openURL(url){
    window.location = url;
}

function getLinkFileSize(url, element){
    var respJson;
    var http = new XMLHttpRequest();
    http.open('GET', url, true);
    http.onreadystatechange = function() {
        if (this.readyState == this.DONE) {
            if (this.status === 200) {
                if(this.response){
                    respJson = JSON.parse(this.response);
                    if(respJson){
                        if(respJson.contentLength){
                            if(element){
                                var size = convertFileSize(respJson.contentLength, true);
                                element.text(size);
                            }
                        }
                    }
                }
            }
        }
    };
    http.send();
    return ('');
}

function convertFileSize(bytes, si, brackets) {
    var size= '';
    var thresh = si ? 1000 : 1024;
    if(Math.abs(bytes) < thresh) {
        size = bytes + ' B';
        if (brackets) {
          size = '(' + size + ')';
        }
        return size;
    }
    var units = si
        ? ['kB','MB','GB','TB','PB','EB','ZB','YB']
        : ['KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];
    var u = -1;
    do {
        bytes /= thresh;
        ++u;
    } while(Math.abs(bytes) >= thresh && u < units.length - 1);

    var unit = units[u];
    var val = bytes.toFixed(1);

    if((units[u] == units[0]) && (val / 1000 >= 0.1)){
        return (val / 1009).toFixed(1) + ' ' + units[1];
    }
    size = bytes.toFixed(1) + ' ' + units[u];
    if (brackets) {
      size = '(' + size + ')';
    }
    return  size;
}

function checkPassword(pwd, idMeter, idText) {
    var meter = document.getElementById(idMeter);
    // fallback meter needed for IE browser compability
    var fallBackMeter = meter.lastElementChild.lastElementChild;
    var text = document.getElementById(idText);

    if (pwd != '') {
        var result = zxcvbn(pwd);
        meter.value = result.score;
        meter.style.display = 'block';
        // 4 is the max score from zxcvbn
        fallBackMeter.style.width =  (result.score/4 * 100)+"%";
        fallBackMeter.style.background =  meterStrengthColors[result.score];
        text.innerHTML = meterStrength[result.score];
    } else {
        text.innerHTML  = ' ';
        meter.value = 0;
        fallBackMeter.style.width = "0%";
        meter.style.display = 'none';
    }
}

function updateQueryStringParameter(key, value) {
  var uri = window.parent.location.href;
  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
  var separator = uri.indexOf('?') !== -1 ? "&" : "?";
  if (uri.match(re)) {
    uri = uri.replace(re, '$1' + key + "=" + value + '$2');
  }
  else {
    uri = uri + separator + key + "=" + value;
  }
  window.history.replaceState(null,null, uri);
}

function getQueryStringParameter(key) {
    var url = window.parent.location.href;
    key = key.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    var regexS = "[\\?&]"+key+"=([^&#]*)";
    var regex = new RegExp( regexS );
    var results = regex.exec( url );
    return results == null ? null : results[1];

}

function addLayerBWaStr(map, ids, restUrlBWaStr, wkt, bboxes, bboxColor, bboxFillOpacity, bboxWeight, inverted) {
  var promises = [];
  ids.forEach(function(id){
    var request =  $.ajax({
      url: restUrlBWaStr + '?id=' + id[0] + '&von=' + id[1] + '&bis=' + id[2],
      dataType: 'json',
      success: function (data) {
        return data;
      }
    });
    promises.push( request);
  });
  if(promises.length > 0) {
    map.spin(true);
    Promise.all(promises).then((values) => {
      var features = [];
      values.forEach(function(data){
        var geometry = data.geometry;
        if (geometry) {
          var geojsonObject = {
            'type': 'FeatureCollection',
            'features': [{
              'type': 'Feature',
              'geometry': {
                'type': geometry.type,
                'coordinates': geometry.coordinates
              }
            }]
          };
          var featureLayer = L.geoJson(geojsonObject, {});
          featureLayer.bindTooltip('<b>' + data.bwastr_name + ' (' + data.bwastrid + ')</b><br>' + data.strecken_name, {direction: 'center'});
          features.push(featureLayer);
        }
      });
      if(features.length > 0) {
        var featureGroup = L.featureGroup(features).addTo(map);
        map.fitBounds(featureGroup.getBounds());
        map.spin(false);
      } else {
        if(wkt) {
          addLayerWKT(map, wkt, bboxes, bboxColor, bboxFillOpacity, bboxWeight, inverted);
        } else if (bboxes) {
          addLayerBounds(map, bboxes, bboxColor, bboxFillOpacity, bboxWeight, inverted);
        }
        map.spin(false);
      }
    });
  } else {
    if(wkt) {
      addLayerWKT(map, wkt, bboxes, bboxColor, bboxFillOpacity, bboxWeight, inverted);
    } else if (bboxes) {
      addLayerBounds(map, bboxes, bboxColor, bboxFillOpacity, bboxWeight, inverted);
    }
  }
}

function addLayerWKT(map, wkt, bboxes, bboxColor, bboxFillOpacity, bboxWeight, inverted) {
  var features = L.geoJSON(JSON.parse(wkt));
  if(features) {
      features.addTo(map);
      map.fitBounds(features.getBounds());
  } else {
      addLayerBounds(map, bboxes, bboxColor, bboxFillOpacity, bboxWeight, inverted);
  }
}

function addLayerBounds(map, bboxes, bboxColor, bboxFillOpacity, bboxWeight, inverted) {
  if(inverted) {
    var geojson = [];
    bboxes.forEach(function(bbox) {
      var y1Coord = bbox.y1;
      var x1Coord = bbox.x1;
      var y2Coord = bbox.y2;
      var x2Coord = bbox.x2;
      if(y1Coord !== 0 && x1Coord !== 0 && y2Coord !== 0 && x2Coord !== 0) {
        if(x1Coord === x2Coord && y1Coord === y2Coord) {
        } else {
          var mapLayerBounds = L.rectangle([[y1Coord, x1Coord], [y2Coord, x2Coord]], {});
          geojson.push(mapLayerBounds.toGeoJSON());
        }
      }
    });
    L.geoJson(geojson, {
      invert: true,
      color: bboxColor,
      fillOpacity: bboxFillOpacity,
      weight: bboxWeight || 1
    }).addTo(map);
  } else {
    bboxes.forEach(function(bbox) {
      var y1Coord = bbox.y1;
      var x1Coord = bbox.x1;
      var y2Coord = bbox.y2;
      var x2Coord = bbox.x2;
      if(y1Coord !== 0 && x1Coord !== 0 && y2Coord !== 0 && x2Coord !== 0) {
        if(x1Coord === x2Coord && y1Coord === y2Coord) {
          var marker = L.marker([y1Coord, x1Coord]);
          marker.bindTooltip(bbox.title, {direction: 'center'});
          map.addLayer(marker);
        } else {
          var mapLayerBounds = L.rectangle([[y1Coord, x1Coord], [y2Coord, x2Coord]], {
            color: bboxColor,
            fillOpacity: bboxFillOpacity,
            weight: bboxWeight || 1
          });
          mapLayerBounds.bindTooltip(bbox.title, {direction: 'center'});
          map.addLayer(mapLayerBounds);
        }
      }
    });
  }
}

function updateURLParamReload(key, value) {
  var pathname = window.parent.location.pathname;
  var search = window.parent.location.search;
  var hash = window.parent.location.hash;
  var uri = pathname + search;
  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
  var separator = uri.indexOf('?') !== -1 ? "&" : "?";
  if (uri.match(re)) {
    uri = uri.replace(re, '$1' + key + "=" + value + '$2');
  }
  else {
    uri = uri + separator + key + "=" + value;
  }
  window.location.href = uri + hash;
}

function getOS() {
  var userAgent = window.navigator.userAgent,
      platform = window.navigator?.userAgentData?.platform || window.navigator.platform,
      macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K'],
      windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'],
      iosPlatforms = ['iPhone', 'iPad', 'iPod'],
      os = null;

  if (macosPlatforms.indexOf(platform) !== -1) {
    os = 'Mac OS';
  } else if (iosPlatforms.indexOf(platform) !== -1) {
    os = 'iOS';
  } else if (windowsPlatforms.indexOf(platform) !== -1) {
    os = 'Windows';
  } else if (/Android/.test(userAgent)) {
    os = 'Android';
  } else if (/Linux/.test(platform)) {
    os = 'Linux';
  }

  return os;
}

function elementInViewport(el) {
    var top = el.offsetTop;
    var left = el.offsetLeft;
    var width = el.offsetWidth;
    var height = el.offsetHeight;

    while(el.offsetParent) {
      el = el.offsetParent;
      top += el.offsetTop;
      left += el.offsetLeft;
    }

    return (
        top >= window.pageYOffset &&
        left >= window.pageXOffset &&
        (top + height) <= (window.pageYOffset + window.innerHeight) &&
        (left + width) <= (window.pageXOffset + window.innerWidth)
    );
}

function getWMTSLayer(wmts, attribution, opacity) {
  return new L.TileLayer(wmts, {
      attribution: attribution,
      opacity: opacity
  });
}

function getWMSLayer(layerUrl, layerName, attribution, opacity) {
  return new L.tileLayer.wms(layerUrl, {
      layers: layerName,
      attribution: attribution,
      opacity: opacity
  });
}

function addLeafletMap(baselayers, defaultEpsg, bounds, latlng, zoom) {
  return addLeafletMapWithId('map', baselayers, defaultEpsg, bounds, latlng, zoom);
}

function addLeafletMapWithId(mapId, baselayers, defaultEpsg, bounds, latlng, zoom) {
  var epsg = L.CRS.EPSG3857;
  if (defaultEpsg) {
      epsg = defaultEpsg;
  }
  var map = new L.Map(mapId, {
      crs: epsg,
      layers: baselayers
  });
  map.attributionControl.setPrefix('<a href="https://leafletjs.com" title="Leaflet">Leaflet</a>');
  if (bounds) {
      map.fitBounds(bounds);
  } else if (latlng) {
      map.setView(latlng, zoom || 6);
  } else {
      map.setView(new L.LatLng(51.3, 10), 6);
  }
  return map;
}

function addLeafletHomeControl(map, title, position, icon, bounds, padding, fontSize) {
  var HomeControl = L.Control.extend({
      options: {
          position: position ? position : 'topleft'
      },
      onAdd: function (map) {
          var container = L.DomUtil.create('div', 'leaflet-control-home leaflet-bar');
          var link = L.DomUtil.create('a', icon, container);
          link.href = '#';
          if (padding) {
              link.style.padding = padding;
          }
          if (fontSize) {
              link.style.fontSize = fontSize;
          }
          link.title = title;
          L.DomEvent.addListener(link, 'click', this._homeClick, this);
          return container;
      },
      _homeClick: function (e) {
          L.DomEvent.stop(e);
          if (bounds) {
              var features = [];
              map.eachLayer(function (layer) {
                  if (layer && layer.getBounds) {
                      features.push(layer);
                  }
              })
              if (features.length > 0) {
                  bounds = L.featureGroup(features).getBounds();
              }
              map.fitBounds(bounds);
          }
      }
  });
  map.addControl(new HomeControl({}));
}

function resizeMap(map) {
  if (map) {
      map._onResize();
  }
}

function disableLeafletMapTouchControl(map) {
  map.removeControl(map.zoomControl);
  map.dragging.disable();
  map.zoomControl.disable();
  map.touchZoom.disable();
  map.doubleClickZoom.disable();
  map.scrollWheelZoom.disable();
  map.boxZoom.disable();
  map.keyboard.disable();
}

function addHitLeafletMap(id, epsg, bboxes, wkt, bwastr, isDetail, leaflet_config) {
    var bounds;
    var boundsX1;
    var boundsY1;
    var boundsX2;
    var boundsY2;
    var y1;
    var x1;
    var y2;
    var x2;

    bboxes.forEach(function(bbox) {
        y1 = bbox.y1;
        x1 = bbox.x1;
        y2 = bbox.y2;
        x2 = bbox.x2;

        if (y1 && x1 && y2 && x2) {
            if(y1 !== 0 && x1 !== 0 && y2 !== 0 && x2 !== 0) {
                if(!boundsX1 || x1 < boundsX1) {
                    boundsX1 = x1;
                }

                if(!boundsY1 || y1 < boundsY1) {
                    boundsY1 = y1;
                }

                if(!boundsX2 || x2 > boundsX2) {
                    boundsX2 = x2;
                }

                if(!boundsY2 || y2 > boundsY2) {
                    boundsY2 = y2;
                }

                var southWest = L.latLng(boundsY1, boundsX1);
                var northEast = L.latLng(boundsY2, boundsX2);
                bounds = L.latLngBounds(southWest, northEast);
            }
        }
    });
    var bgWmtsUrl = leaflet_config.bg.layer.wmts.url;
    var bgWmsUrl = leaflet_config.bg.layer.wms.url;
    var bgWmsName = leaflet_config.bg.layer.wmts.name;
    var bgAttribution = leaflet_config.bg.layer.attribution;
    var bgOpacity = leaflet_config.bg.layer.opacity;

    var bgLayer = getWMTSLayer(bgWmtsUrl, bgAttribution, bgOpacity);
    if (bgWmsUrl && bgWmsName) {
        bgLayer = getWMSLayer(bgWmsUrl, bgWmsName, bgAttribution, bgOpacity);
    }
    var map = addLeafletMapWithId(id, bgLayer, epsg, bounds, null , 10);
    if (isDetail) {
        map.gestureHandling.enable();
        addLeafletHomeControl(map, 'Zoom auf initialen Kartenausschnitt', 'topleft', 'ic-ic-center', bounds, '', '23px');
    } else {
        disableLeafletMapTouchControl(map);
    }
    if(bounds) {
        map.fitBounds(bounds);
    }

    var bboxColor = leaflet_config.bbox.color;
    var bboxFillOpacity = leaflet_config.bbox.opacity;
    var bboxWeight = leaflet_config.bbox.weight;
    var bboxInverted = leaflet_config.bbox.inverted;

    if (wkt) {
      addLayerWKT(map, wkt, bboxes, bboxColor, bboxFillOpacity, bboxWeight, bboxInverted);
    } else if (bwastr) {
      addLayerBWaStr(map, bwastr, '$restUrlBWaStr', wkt, bboxes, bboxColor, bboxFillOpacity, bboxWeight, bboxInverted);
    } else {
      addLayerBounds(map, bboxes, bboxColor, bboxFillOpacity, bboxWeight, bboxInverted);
    }
    return map;
}