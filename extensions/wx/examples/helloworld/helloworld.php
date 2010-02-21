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

    class HelloWorldWidget extends WikiWidget {
        function meta() {
            return array(
                'name'          => 'Hello World Widget',
                'description'   => 'Say Hello',
                'author'        => 'Andy Armstrong',
                'url'           => array(
                    'Example Wiki Widget' =>
                    'http://hexten.net/wiki/index.php?title=Example_Wiki_Widget'
                ),
                'version'       => '0.1',
                'example'       => array(),
            );
        }

        function render() {
            $this->output(
                $this->tag('p', 'Hello, World!')
            );
        }
    }
?>
