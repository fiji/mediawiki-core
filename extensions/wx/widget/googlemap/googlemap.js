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

// Support functions for Google Maps

function wxCreateMarker(feature) {
    var point  = new GLatLng(feature.lat, feature.lon);
    var marker = new GMarker(point);

    if (feature.type == 'marker') {
        if (feature.text != '') {
            GEvent.addListener(marker, "click", function() {
                marker.openInfoWindowHtml(feature.text);
            });
        }
    }
    else {
        // Huh?
    }
    
    return marker;
}

function wxAddMarkers(map, features) {
    for (f in features) {
        map.addOverlay(wxCreateMarker(features[f]));
    }
}

function wxRound(x, digits) {
    var scale = Math.pow(10, digits);
    var rep   = '' + Math.round(x * scale) / scale;
    var point = rep.indexOf('.');
    if (point == -1) {
        return rep;
    }
    else {
        return rep.substr(0, point + digits + 1);
    }
}

function wxLocationControl() { 
    // Empty constructor
}

wxLocationControl.prototype = new GControl();

wxLocationControl.prototype.showPopup = function(map) {
    var here = map.getCenter();
    var mark = "    <marker lat=\"" + wxRound(here.lat(), 6) 
             +          "\" lon=\"" + wxRound(here.lng(), 6) + "\">\n"
             + "    </marker>\n";

    var edit      = document.getElementById('wpTextbox1');
    var text_area = wxElement('textarea');

    text_area.value        = mark;
    text_area.style.width  = '400px';
    text_area.style.height = '100px';
    
    var form    = wxElement('form', text_area);
    var message = null;
    
    if (edit) {
        var link = wxElement('a', wxTextElement('here'));
        // Insert contents of textarea into edit box
        link.onclick = function() {
            wxInsertAtCursor(edit, text_area.value);
        };
        
        message = wxElement('p',
            wxTextElement('Click '), 
            link, 
            wxTextElement(' to insert this node at the cursor.')
        );
    }
    else {
        message = wxElement('p', 
            wxTextElement('Copy & paste this code to create a new marker:')
        );
    }
    
    map.openInfoWindow(here, wxElement('div', message, form));
}

wxLocationControl.prototype.initialize = function(map) {
    var container = document.createElement("div");

    var button = document.createElement('img');
    button.src = wxGetHome() + '/widget/googlemap/locate.png';
    container.appendChild(button);

    var self = this;
    GEvent.addDomListener(button, "click", function() {
        self.showPopup(map);
    });

    map.getContainer().appendChild(container);
    return container;
}

wxLocationControl.prototype.getDefaultPosition = function() {
    return new GControlPosition(G_ANCHOR_TOP_LEFT, new GSize(18, 105));
}
