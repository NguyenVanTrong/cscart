(function(_, $) {
    (function($) {

        var map = null;
        var saved_point = null;
        var marker = null;
        var map_params = null;

        var latitude = 0;
        var longitude = 0;
        var zoom = 0;

        var latitude_name = '';
        var longitude_name = '';
        var map_container = '';

        function updatePoint(point)
        {
            if (saved_point && marker) {
                map.geoObjects.remove(marker);
            }

            marker = new ymaps.Placemark(point);

            map.geoObjects.add(marker);

            saved_point = point;

        }

        function addMapListeners()
        {
            var searchControl = map.controls.get('searchControl');
            searchControl.events.add('resultselect', function (e) {
                var index = e.get('index');
                searchControl.getResult(index).then(function (result) {
                    geoResult = result.geometry.getCoordinates();
                    updatePoint(geoResult);
                    result.getParent().remove(result);
                });
            });

            map.events.add('click', function(event) {
                var coords = event.get('coords');
                updatePoint(coords);
            });
        }

        var methods = {

            init: function(options, callback) {

                if (! ('ymaps' in window)) {
                    $.getScript('//api-maps.yandex.ru/2.1/?lang=' + options.language, function() {
                        ymaps.ready(function() {
                            $.ceMap('init', options, callback);
                        });
                    });

                    return false;
                }

                latitude = options.latitude;
                longitude = options.longitude;
                map_container = options.map_container;

                storeData = options.storeData;
                zoom = options.zoom;

                // Required fields - zoom, center
                map_params = {
                    zoom: 12,
                    type: 'yandex#map',
                    center: [latitude, longitude],
                    controls: ['default'],
                }

                if (_.area == 'A') {
                    $.extend(map_params, {
                        draggableCursor: 'crosshair',
                        draggingCursor: 'pointer',
                    });
                } else {
                    $.extend(map_params, {
                        zoom: zoom,
                        controls: options.controls,
                    });
                }

                if (typeof(callback) == 'function') {
                    callback();
                }
            },

            showDialog: function(country_field, city_field, latitude_field, longitude_field) {

                var params_dialog = {
                    href: "",
                    keepInPlace: false,
                    dragOptimize: true
                };

                $('#map_picker').ceDialog('open', params_dialog);

                saved_point = null;
                marker = null;

                latitude_name = latitude_field;
                longitude_name = longitude_field;

                latitude = $('#' + latitude_name + '_hidden').val();
                longitude = $('#' + longitude_name + '_hidden').val();

                var map_center = null;

                if (map) {
                    map.destroy();
                }
                map = new ymaps.Map(document.getElementById(options.map_container), map_params);

                if (latitude && longitude) {
                    map_center = [latitude, longitude];
                    map.setCenter(map_center);
                    updatePoint(map_center);
                    addMapListeners();

                } else if ($('#' + city_field).val()) {
                    var address = '';
                    var value = $('#' + city_field).val();
                    if (value) {
                        var city = value;
                        address = value;
                    }

                    ymaps.geocode(address).then(function(results) {
                        if (city && city.length) {
                            map.setZoom(10);
                        }

                        $('#' + map_container).show();
                        map_center = results.geoObjects.get(0).geometry.getCoordinates();
                        map.setCenter(map_center);
                        addMapListeners();

                    }, function() {
                        fn_alert($.tr('text_address_not_found') + ': ' + address);
                    });

                } else {
                    map_center = [latitude, longitude];
                    map.setCenter(map_center);
                    updatePoint(map_center);
                    addMapListeners();
                }
            },

            show: function(options)
            {
                if (!map_params) {
                    return $.ceMap('init', options, function() {
                        $.ceMap('show', options);
                    });
                }

                map = new ymaps.Map(document.getElementById(options.map_container), map_params);

                bounds = map.getBounds()
                markers = Array();
                infoWindows = Array();

                var marker;

                for (var keyvar = 0; keyvar < storeData.length; keyvar++) {

                    //bounds.extend(marker.position);

                    //balloon content collecting
                    var marker_html = '<div style="padding-right: 10px"><strong>' + storeData[keyvar]['name'] + '</strong><p>';

                    if (storeData[keyvar]['city'] != '') {
                        marker_html += storeData[keyvar]['city'] + ', ';
                    }
                    if (storeData[keyvar]['country_title'] != '') {
                        marker_html += storeData[keyvar]['country_title'];
                    }

                    marker_html += '</p><\/div>';

                    marker = new ymaps.Placemark([ storeData[keyvar]['latitude'], storeData[keyvar]['longitude'] ], {
                        balloonContentBody: marker_html,
                    });

                    map.geoObjects.add(marker);

                    markers.push(marker);
                }

                if (storeData.length == 1) {
                    map.setCenter(marker.geometry.getCoordinates());

                    map.setZoom(zoom);

                } else {
                    ymaps.geoQuery(map.geoObjects).applyBoundsToMap(map);
                }
            },

            saveLocation: function()
            {
                if (saved_point) {
                    $('#' + latitude_name).val(saved_point[0]);
                    $('#' + latitude_name + '_hidden').val(saved_point[0]);
                    $('#' + longitude_name).val(saved_point[1]);
                    $('#' + longitude_name + '_hidden').val(saved_point[1]);
                }

                saved_point = null;
            },

            viewLocation: function(latitude, longitude)
            {
                map.setCenter([latitude, longitude]);
                map.setZoom(zoom);
            },

            viewLocations: function()
            {
                ymaps.geoQuery(map.geoObjects).applyBoundsToMap(map);
            }
        }

        $.extend({
            ceMap: function(method) {
                if (methods[method]) {
                    return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
                } else {
                    $.error('ty.map: method ' +  method + ' does not exist');
                }
            }
        });
    })($);

    $(document).ready(function() {

        $('.cm-map-dialog').on('click', function () {
            $.ceMap('showDialog', 'elm_country', 'elm_city', 'elm_latitude', 'elm_longitude');
        });

        $('.cm-map-save-location').on('click', function () {
            $.ceMap('saveLocation');
        });

        $('.cm-map-view-location').on('click', function () {
            var jelm = $(this);
            var latitude = jelm.data('ca-latitude');
            var longitude = jelm.data('ca-longitude');

            $.ceMap('viewLocation', latitude, longitude);
        });

        $('.cm-map-view-locations').on('click', function () {
            $.ceMap('viewLocations');
        });

    });
}(Tygh, Tygh.$));

