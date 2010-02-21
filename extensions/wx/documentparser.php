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

    class DocumentParser {
        var $p       = null;
        var $stack   = null;
    
        function __construct() {
        
        }
    
        function __destruct() {
            $this->deleteParser();
        }
    
        private function deleteParser() {
            if ($this->p) {
                xml_parser_free($this->p);
                $this->p = null;
            }
        }

        private function startTag($p, $name, $attr) {
            $cname = strtolower($name);
            $cattr = array();
            foreach ($attr as $n => $v) {
                $cattr[strtolower($n)] = $v;
            }

            // Fake inner text. We need to do this /before/ pushing the new
            // stack frame
            $this->inner(wxOpenTag($cname, $cattr));
        
            $frame = array(
                'name'      => $cname,
                'attr'      => $cattr,
                'text'      => '',
                'inner'     => '',
                'nodes'     => array()
            );
        
            array_push($this->stack, $frame);
        }
    
        private function endTag($p, $name) {
            $cname = strtolower($name);
            $frame = array_pop($this->stack);

            if (!$frame) {
                throw new Exception("Parse stack empty"); 
            }
        
            if ($frame['name'] != $cname) {
                throw new Exception("Expected </$cname> got </" . $frame['name'] . ">");
            }

            $tos = count($this->stack) - 1;
            $this->stack[$tos]['nodes'][] =& $frame;

            $this->inner($frame['inner'] . wxCloseTag($cname));
        }

        private function charData($p, $text) {
            $this->stack[count($this->stack)-1]['text'] .= $text;
            $this->inner($text);
        }
    
        private function inner($text) {
            $this->stack[count($this->stack)-1]['inner'] .= $text;
        }
    
        function parse($data) {
            $this->deleteParser();
            $this->p = xml_parser_create();
            xml_set_object($this->p, $this);
            xml_set_element_handler($this->p, 'startTag', 'endTag');
            xml_set_character_data_handler($this->p, 'charData');
        
            $this->stack = array(
                array(
                    'text'  => '',
                    'inner' => '',
                    'nodes' => array()
                )
            );
        
            if (!xml_parse($this->p, $data, true)) {
                $code = xml_get_error_code($this->p);
                $msg  = xml_error_string($code);
                $col  = xml_get_current_column_number($this->p);
                $line = xml_get_current_line_number($this->p);
                throw new Exception("$msg at line $line, column $col");
            }
        
            $this->deleteParser();
        
            return $this->stack[0];
        }
    }

?>
