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

    require_once(dirname(__FILE__) . '/../../externalcmd.php');

    class GraphVizWidget extends WikiWidget {
        function meta() {
            return array(
                'name'          => 'GraphViz Widget',
                'description'   => 'Embed dot diagram',
                'author'        => 'Andy Armstrong',
                'url'           => array(
                    'Graphviz Wiki Widget' =>
                    'http://hexten.net/wiki/index.php?title=GraphViz_Widget'
                ),
                'version'       => '0.1',
                'example'       => array(
                    'style' => 'dot, neato, twopi, circo or fdp'
                ),
            );
        }

        protected function initialize() {
            parent::initialize();
            $this->setDefaults(array(
                'style'     => 'dot',
                'title'     => 'Graph',
            ));
        }

        function getFilePath($url_path) {
            return $_SERVER['DOCUMENT_ROOT'] . $url_path;
        }

        function getCachePath() {
            return $this->needConfig('cache') 
                 . '/' 
                 . $this->type();
        }

        // Synthesize a filename
        function getImageName($suffix = 'png') {
            $name = $this->getCachePath() 
                  . '/' . md5($this->title()->getText() . $this->fountain()) 
                  . '.' . $suffix;
            return $name;
        }

        function render() {
            $helper = $this->needConfig('helper');
            $style = $this->need('style');
            if (!($prog = $helper[$style])) {
                throw new Exception(
                    "Unknown style: $style. Valid styles are: " 
                  . join(', ', array_keys($helper))
                );
            }
            
            $img_name = $this->getImageName();
            $img_file = $this->getFilePath($img_name);

            $img_dir  = dirname($img_file);
            if (!is_dir($img_dir) && !@mkdir($img_dir, 0777, true)) {
                throw new Exception("Can't create directory: $img_dir");
            }
            
            $xcmd = new ExternalCmd($prog, array('-Tpng', '-o', $img_file));
            $xcmd->setInput($this->input());
            $rc = $xcmd->run();
            
            # Not very satisfactory...
            if ($rc) {
                throw new Exception($xcmd->getErrorOutput());
            }
            
            $this->output(
                $this->closedTag('img', array(
                    'src'  => $img_name, 
                    'alt'  => $this->need('title')
                ))
            );
        }
    }
?>
