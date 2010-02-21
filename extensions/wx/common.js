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

// Support functions for extensions

function _wxHook(type, f) {
    var current = window[type];
    
    if (current) {
        window[type] = function() {
            current();
            f();
        }
    }
    else {
        window[type] = f;
    }
}

function wxLoadHook(f) {
    _wxHook('onload', f);
}

function wxUnloadHook(f) {
    _wxHook('onunload', f);
}

// http://alexking.org/blog/2003/06/02/inserting-at-the-cursor-using-javascript/
function wxInsertAtCursor(field, text) {
    //IE support
    if (document.selection) {
        field.focus();
        sel = document.selection.createRange();
        sel.text = text;
    }
    //MOZILLA/NETSCAPE support
    else if (field.selectionStart || field.selectionStart == '0') {
        var startPos = field.selectionStart;
        var endPos   = field.selectionEnd;

        field.value = field.value.substring(0, startPos)
                    + text
                    + field.value.substring(endPos, field.value.length);

    } else {
        field.value += text;
    }
}

// Construct a named element. Optional args after the first are elements
// to attach as children
function wxElement(name) {
    var el = document.createElement(name);
    for (var i = 1; i < arguments.length; i++) {
        el.appendChild(arguments[i]);
    }
    return el;
}

function wxTextElement(str) {
    return document.createTextNode(str);
}

function wxGetHome() {
    return wgScriptPath + '/extensions/wx';
}

// Misc utility stuff

function wxDescribePeriod(delta) {
    var spans = [
        [ 'a second', 'seconds', 1000       ],
        [ 'a minute', 'minutes',   60       ],
        [ 'an hour',  'hours',     60       ],
        [ 'a day',    'days',      24       ],
        [ 'a week',   'weeks',      7       ],
        [ 'a month',  'months',     4.34812 ],
        [ 'a year',   'years',     12       ]
    ];
    
    delta = Math.abs(delta);

    var ns = spans.length;
    for (var s = 0; s < ns; s++) {
        var span = spans[s];
        delta /= span[2];
        if (s == ns-1 || delta < spans[s+1][2]) {
            var about = Math.round(delta);
            if (about < 1) {
                return 'less than ' + span[0];
            }
            else if (about == 1) {
                return 'about ' + span[0];
            }
            else {
                return about + ' ' + span[1];
            }
        }
    }
    
    // Shouldn't happen
    return 'whenever';
}

function wxRelativeTime(ts) {
    ts -= new Date().getTime();

    if (ts < 0) {
        return wxDescribePeriod(ts) + ' ago';
    }
    else {
        return 'in ' + wxDescribePeriod(ts);
    }
}
