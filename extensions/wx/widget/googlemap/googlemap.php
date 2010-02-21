<?php
    // WikiWidgets: Widgets for MediaWiki
    // Copyright (C) 2007, Andy Armstrong, andy@hexten.net
    // 
    // This program is free software; you can redistribute it and/or modify
    // it under the terms of the GNU General Public License as published by
    // the Free Software Foundation; either version 2 of the License, or
    // (at your option) any later version.
    // 
    // This program is distributed in the hope that it will be useful,
    // but WITHOUT ANY WARRANTY; without even the implied warranty of
    // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    // GNU General Public License for more details.
    // 
    // You should have received a copy of the GNU General Public License along
    // with this program; if not, write to the Free Software Foundation, Inc.,
    // 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

    class GoogleMapWidget extends WikiWidget {
        function meta() {
            return array(
                'name'          => 'Google Maps Widget',
                'description'   => 'Embed Google Map',
                'author'        => 'Andy Armstrong',
                'url'           => array(
                    'Using the Google Maps Widget' =>
                    'http://hexten.net/wiki/index.php/Google_Maps_Widget'
                ),
                'version'       => '0.1'
            );
        }

        protected function initialize() {
            parent::initialize();
            $this->setDefaults(array(
                'width'     => 500,
                'height'    => 300,
            ));
        }

        protected function parseData($doc) {
            $features = array();
            foreach ($doc['nodes'] as $nd) {
                $features[] = array(
                    'text'  => $this->parseWikiMarkup( trim($nd['inner']) ),
                    'type'  => $nd['name'],
                    'lat'   => $nd['attr']['lat'],
                    'lon'   => $nd['attr']['lon'],
                );
            }
            return $features;
        }
    
        protected function getBounds($features) {
            $bounds = null;
        
            foreach ($features as $f) {
                if (is_null($bounds)) {
                    $bounds = array($f['lat'], $f['lon'], $f['lat'], $f['lon']);
                }
                else {
                    $bounds[0] = min($bounds[0], $f['lat']);
                    $bounds[1] = min($bounds[1], $f['lon']);
                    $bounds[2] = max($bounds[2], $f['lat']);
                    $bounds[3] = max($bounds[3], $f['lon']);
                }
            }
        
            return $bounds;
        }

        protected function distance($lat1, $lon1, $lat2, $lon2) {
            $EARTH_RADIUS = 6378137.0;
            $DEG_TO_RAD   = 4 * atan2(1, 1) / 180.0;

            $dist = 0;

            $lat1 *= $DEG_TO_RAD;
            $lon1 *= $DEG_TO_RAD;
            $lat2 *= $DEG_TO_RAD;
            $lon2 *= $DEG_TO_RAD;

            $sdlat = sin(($lat1 - $lat2) / 2.0);
            $sdlon = sin(($lon1 - $lon2) / 2.0);
            $res   = sqrt($sdlat * $sdlat + cos($lat1) * cos($lat2) * $sdlon * $sdlon);

            if ($res > 1.0) {
                $res = 1.0;
            }
            else if ($res < -1.0) {
                $res = -1.0;
            }
        
            $dist += 2.0 * asin($res);

            return $dist * $EARTH_RADIUS;
        }
        
        function render() {
            $doc      = $this->inputDocument();
            $features = $this->parseData($doc);

            $lat    = 0;
            $lon    = 0;
            $zoom   = 2;
            $width  = $this->need('width');
            $height = $this->need('height');

            $bounds = $this->getBounds($features);
            if ($bounds) {
                $lat = ($bounds[0] + $bounds[2]) / 2;
                $lon = ($bounds[1] + $bounds[3]) / 2;
            
                $mh  = $this->distance($bounds[0], $lon, $bounds[2], $lon);
                $mw  = $this->distance($lat, $bounds[1], $lat, $bounds[3]);

                if ($mw > 0 || $mh > 0) {
                    $hs  = $mw > 0 ? ($width  / $mw) : 10000;
                    $vs  = $mh > 0 ? ($height / $mh) : 10000;

                    $scale = min($hs, $vs) * 80000;
                    $zoom  = max(0, min(floor(log($scale) / log(2)), 17));
                }
                else {
                    $zoom  = 14;
                }
            }

            // Allow overrides
            $lat    = $this->arg('lat',    $lat);
            $lon    = $this->arg('lon',    $lon);
            $zoom   = $this->arg('zoom',   $zoom);

            $locate = $this->arg('locate', 1);

            $id     = $this->fountain();
    
            $map_src = $this->makeURI('http://maps.google.com/maps', array(
                'file'  => 'api',
                'v'     => 2,
                'key'   => $this->needConfig('api_key')
            ));

            $this->requireSupportScript();
            $this->requireScript($map_src);
            $this->requireMyScript('googlemap.js');

            $this->output(
                $this->openScript(),
                "wxLoadHook(function() {\n",
                "    if (GBrowserIsCompatible()) {\n",
                "        var features = ", json_encode($features), ";\n",
                "        var map = new GMap2(document.getElementById(", json_encode($id), "));\n",
                "        map.addControl(new GSmallMapControl());\n",
                "        map.addControl(new GMapTypeControl());\n"
            );

            if ($locate) {
                $this->output("        map.addControl(new wxLocationControl());\n");
            }

            $this->output(
                "        map.setCenter(new GLatLng(", json_encode(1 * $lat), ", ", 
                                                      json_encode(1 * $lon), "), ",
                                                      json_encode(1 * $zoom), ");\n",
                "        wxAddMarkers(map, features);\n",
                "    }\n",
                "});\n\n",
                "wxUnloadHook(GUnload);\n",
                $this->closeScript()
            );

            $this->output(
                wxOpenTag('div', array(
                    'id'    => $id,
                    "style" => "width: ${width}px; height: ${height}px"
                )),
                wxCloseTag('div') . "\n"
            );
        }
    }

?>
