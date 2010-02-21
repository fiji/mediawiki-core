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

    class TwitterWidget extends WikiWidget {
        function meta() {
            return array(
                'name'          => 'Twitter Widget',
                'description'   => 'Embed Twitter Status',
                'author'        => 'Andy Armstrong',
                'url'           => array(
                    'Using the Twitter Widget' =>
                    'http://hexten.net/wiki/index.php/Twitter_Widget'
                ),
                'version'       => '0.1',
                'example'       => array(
                    'uid' => 'twitter user id'
                ),
            );
        }

        protected function initialize() {
            parent::initialize();
            $this->setDefaults(array(
                'count'     => 1
            ));
        }
        
        function render() {
            $uid       = $this->need('uid');
            $count     = min($this->needConfig('max_count'), $this->need('count'));
            
            if (!preg_match('/^\d+$/', $uid)) {
                throw new Exception('uid must be numeric');
            }

            // XML
            //   http://twitter.com/statuses/user_timeline/1022831.xml?count=4
            // JSON
            //   http://twitter.com/statuses/user_timeline/1022831.json?count=4
            // JSON wrapped in a function call - suitable as <script src>
            //   http://twitter.com/statuses/user_timeline/1022831.json?callback=someFunc&count=4

            $name     = $this->fountain();
            $base_url = 'http://www.twitter.com/statuses/user_timeline/' 
                      . htmlentities($uid) 
                      . '.json';
            
            $src_url  = $this->makeURI($base_url, array(
                'callback'  => $name,
                'count'     => $count
            ));

            $this->requireSupportScript();
            $this->requireMyScript('twitter.js');
            
            // Data to pass to wxTwitterUpdate()
            $context = array(
                'name'  => $name,
                'argv'  => $this->argv,
                'info'  => $this->info,
            );
            
            $this->output(
                $this->openScript(),
                "    function $name(obj) {\n",
                "        wxTwitterUpdate(obj, ", json_encode($context), ");\n",
                "    }\n",
                $this->closeScript(),
                $this->tag('div', '', array('id' => $name)), "\n",
                $this->script($src_url)
            );
        }
    }
?>
