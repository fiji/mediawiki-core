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

    // Global flag: Common helper JS has been included
    $WX_GOT_JS = array();

    # Install hook to post process our encoded output
    if (!function_exists('wxProcessEncodedOutput')) {
        $wgHooks['ParserAfterTidy'][] = 'wxProcessEncodedOutput';
        function wxProcessEncodedOutput( &$out, &$text ) {
            wxDecodeLiterals($text);
            return true;
        }
    }

    function wxDecodeLiterals(&$text) {
        $text = preg_replace(
            '/WX_ENCODED_CONTENT-([0-9a-zA-Z\/+]+=*)-/esm',
            'base64_decode("$1")',
            $text
        );
    }

    function wxLiteral($output) {
        return 'WX_ENCODED_CONTENT-' . base64_encode($output) . '-';
    }

    function wxHome() {
        global $wgScriptPath;
        return $wgScriptPath;
    }

    function wxExtensions() {
        return wxHome() . '/extensions/wx';
    }

    function wxRequireScript($src, $extra_if_included = '') {
        global $WX_GOT_JS;

        if (isset($WX_GOT_JS[$src])) {
            return '';
        }
        
        $WX_GOT_JS[$src] = true;

        return wxScript($src) . $extra_if_included;
    }

    function wxScriptSupport($extra_if_included = '') {
        return wxRequireScript(
            wxExtensions() . '/common.js', 
            $extra_if_included
        );
    }

    function wxArg(&$argv, $name, $def = null) {
        if (isset($argv[$name])) {
            return $argv[$name];
        }
        return $def;
    }
    
    function wxParseQuery($qs) {
        $args = explode('&', $qs);
        $argv = array();
        foreach ($args as $a) {
            list($n, $v) = explode('=', $a, 2);
            $argv[urldecode($n)] = urldecode($v);
        }
        return $argv;
    }

    function wxMakeURI($base, $args = null) {
        if ($args !== null) {
            $a = array();
            while (list($n, $v) = each($args)) {
                $a[] = urlencode($n) . '=' . urlencode($v);
            }
            
            if (count($a)) {
                $sep = (strpos($base, '?') === false) ? '?' : '&';
                $base .= $sep . implode('&', $a);
            }
        }
        return $base;
    }

    function _wxOpenTag($name, $args, $closed) {
        $tag = "<$name";
        if (!is_null($args)) {
            while (list($n, $v) = each($args)) {
                $tag .= " $n";
                if (!is_null($v)) {
                    $tag .= "=\"" . htmlentities($v) . "\"";
                }
            }
        }
        $tag .= $closed ? ' />' : '>';
        return $tag;
    }

    function wxOpenTag($name, $args = null) {
        return _wxOpenTag($name, $args, false);
    }

    function wxCloseTag($name) {
        return "</$name>";
    }
    
    function wxClosedTag($name, $args = null) {
        return _wxOpenTag($name, $args, true);
    }
    
    function wxEmptyTag($name, $args = null) {
        return wxOpenTag($name, $args) . wxCloseTag($name);
    }

    function wxOpenScript() {
        return wxOpenTag('script', array(
            'type'  => 'text/javascript'
        )) . "\n//<![CDATA[\n";
    }
    
    function wxCloseScript() {
        return "//]]>\n" . wxCloseTag('script') . "\n";
    }
    
    function wxScript($src) {
        return wxEmptyTag('script', array(
            'src'   => $src,
            'type'  => 'text/javascript'
        )) . "\n";
    }
    
    function wxTag($name, $text, $args = null) {
        if (is_array($text)) {
            $out = array();
            foreach ($text as $t) {
                $out[] = wxTag($name, $t, $args);
            }
            return join('', $out);
        }
        else {
            return wxOpenTag($name, $args) . $text . wxCloseTag($name);
        }
    }

    function wxParseWikiMarkup( $text, &$parser ) {
        // global $wgUser, $wgTitle;
        // 
        // Don't bother with Stub because we know it's loaded by now
        // $np = new Parser;
        // $text = $np->preSaveTransform( 
        //     $text, $wgTitle, $wgUser, ParserOptions::newFromUser( $wgUser ) 
        // );

        $text = $parser->recursiveTagParse( $text );
        $parser->replaceLinkHolders( $text );
        return $text;
    }

    function wxFormatErrors($errs) {
        $border_width  = 2;
        $border_colour = 'red';

        $style = "border: ${border_width}px solid ${border_colour}; "
               . "padding: 4px; "
               . "margin: 4px";

        $err_list = array();
        foreach ($errs as $msg) {
            $err_list[] = wxTag('p', htmlentities($msg));
        }

        return wxTag('div', 
              wxTag('h3', 'Errors from Wiki Widgets') 
            . "The following errors occurred:<br />\n"
            . join('', $err_list),
            array('style' => $style)
        );
    }
?>
