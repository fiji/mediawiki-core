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

    class IndexWidget extends WikiWidget {
        function meta() {
            return array(
                'name'          => 'Index Widget Widget',
                'description'   => 'Display Information about Widgets',
                'author'        => 'Andy Armstrong',
                'url'           => 'http://hexten.net',
                'version'       => '0.1'
            );
        }

        protected function outputWikiFromFile($src) {
            $text = file_get_contents($src);
            $this->output(
                $this->parseWikiMarkup($text)
            );
        }

        protected function describeWidget($name, $path) {
            $widget = wxWidgetForType($name);
            $widget->bind(
                '', array('debug' => $this->isDebug),
                $this->parser(), wxWidgetInfo($name)
            );
            $meta   = $widget->meta();
            $this->output($this->tag('h2', $meta['name']));
            $widget->renderAbout();
            $this->output($widget->getOutput());
            $readme = "$path/readme.wiki";
            if (file_exists($readme)) {
                $this->outputWikiFromFile($readme);
            }
        }
        
        protected function walkWidgets() {
            $widgets = realpath(dirname(__FILE__) . '/..');
            if ($dh = opendir($widgets)) {
                while (false !== ($file = readdir($dh))) {
                    if ($file != $this->type() && preg_match('/^\w+$/', $file)) {
                        $this->describeWidget($file, "$widgets/$file");
                    }
                }
                closedir($dh);
            }
            else {
                throw new Exception("Can't read $widgets");
            }
        }
        
        function render() {
            $this->disableCache();
            $this->outputWikiFromFile(dirname(__FILE__) . '/readme.wiki');
            $this->walkWidgets();
        }
    }

?>
