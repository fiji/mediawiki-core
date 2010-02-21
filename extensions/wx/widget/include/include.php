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

    class IncludeWidget extends WikiWidget {
        function meta() {
            return array(
                'name'          => 'Include Widget',
                'description'   => 'Allow PHP to be included on a page',
                'author'        => 'Andy Armstrong',
                'url'           => array(
                    'Include Widget' =>
                    'http://hexten.net/wiki/index.php?title=Include_Widget'
                ),
                'version'       => '0.1',
                'example'       => array(),
            );
        }

        function render() {
            $this->disableCache();

            $src = $this->arg('src');
            
            if (!preg_match('/^[\w]+$/', $src)) {
                throw new Exception('Include src must be alphanumeric');
            }

            $script = dirname(__FILE__)
                    . '/scripts/' . $src . '/index.php';

            ob_start();
            $rc = @include($script);
            $out = ob_get_contents();
            ob_end_clean();

            if (!$rc) {
                throw new Exception("Script \"$src\" not found ($script)");
            }

            $this->output($out);
        }
    }
?>
