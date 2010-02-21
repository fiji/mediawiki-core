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

    class GoogleGadgetWidget extends WikiWidget {
        function meta() {
            return array(
                'name'          => 'Google Gadget Widget',
                'description'   => 'Embed Google Gadget',
                'author'        => 'Andy Armstrong',
                'url'           => array(
                    'Using the Google Gadget Widget' =>
                    'http://hexten.net/wiki/index.php/Google_Gadget_Widget'
                ),
                'version'       => '0.1',
                'example'       => array(
                    'url'   => 'base url of gadget',
                ),
            );
        }

        protected function initialize() {
            parent::initialize();
            $this->setDefaults(array(
                'width'     => 320,
                'height'    =>  75,
                'title'     => 'Google Gadget',
                'border'    => '#ffffff|3px,1px solid #999999',
                'synd'      => 'open',
            ));
        }
        
        function render() {
            $args = $this->args();
            
            $args['w']      = $args['width'];
            $args['h']      = $args['height'];
            $args['output'] = 'js';

            $remove = array('width', 'height', 'debug', 'disable');
            foreach ($remove as $r) {
                unset($args[$r]);
            }

            $this->output(
                $this->script(
                    $this->makeURI('http://gmodules.com/ig/ifr', $args)
                )
            );
        }
    }
?>
