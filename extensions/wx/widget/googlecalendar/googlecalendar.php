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

    class GoogleCalendarWidget extends WikiWidget {
        function meta() {
            return array(
                'name'          => 'Google Calendar Widget',
                'description'   => 'Embed Google Calendar',
                'author'        => 'Andy Armstrong',
                'url'           => array(
                    'Using the Google Calendar Widget' =>
                    'http://hexten.net/wiki/index.php/Google_Calendar_Widget'
                ),
                'version'       => '0.1',
                'example'       => array(
                    'src' => 'calendar source id'
                ),
            );
        }

        protected function initialize() {
            parent::initialize();
            $this->setDefaults(array(
                'width'     => 800,
                'height'    => 750,
            ));
        }
        
        function render() {
            // urldecode so people can cut/paste a Google calendar URL
            $src    = urldecode($this->need('src'));
            $width  = $this->need('width');
            $height = $this->need('height');

            $url = $this->makeURI('http://www.google.com/calendar/embed', array(
                'src' => $src
            ));

            $this->output(
                $this->tag('iframe', '', array(
                    'src'           => $url,
                    'style'         => 'border-width: 0',
                    'width'         => $width,
                    'height'        => $height,
                    'frameborder'   => 0,
                ))
            );
        }
    }
?>
