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

    require_once(dirname(__FILE__) . '/googlemap.php');


    wxAddWidget('googlemap', array(
        'class'  => 'GoogleMapWidget',

        'config' => array(
            'per_host' => array(
                
                // Add your Google Maps API key here
                
                'nenthead.org.uk'           => array(
                    'api_key' =>
                    'ABQIAAAAVyB2T4w5ugEDc0dyDm2JsRSZWlZM7DyoXUOnyUR_3sUPU5_WbxQCkIeVUYUXcAH3oRfrv0O9nI5WlA',
                ),
                'nenthead.voodoo.ripley'    => array(
                    'api_key' =>
                    'ABQIAAAAVyB2T4w5ugEDc0dyDm2JsRTM53K8shsvIFh8JJ315mozRLPZ_xQWcqN-Otk0eL_3WnDt2VFh8knsEA',
                ),
                'cumbria.pm.org'            => array(
                    'api_key' =>
                    'ABQIAAAAVyB2T4w5ugEDc0dyDm2JsRRCvVmTtkBxW2g6hg0jGpfJ2i9UeRSJsvpRiDWeTrHbr7xeTWp2ylwBXA',
                ),
                'hexten.net'                => array(
                    'api_key' =>
                    'ABQIAAAAVyB2T4w5ugEDc0dyDm2JsRQP2ubrLxYY5QZUJTkbW0YyoXO-pxTRzltzTiSawNxGGoVhfN7zN4HHkg',
                ),
                'hexten.voodoo.ripley'      => array(
                    'api_key' =>
                    'ABQIAAAAVyB2T4w5ugEDc0dyDm2JsRS5UgqVc_V8pl1Pif976mdIBajS1hQxDOe7jAU3w57KLf1Jgh9bgQ754g',
                ),
            ),
        ),
    ));

?>
