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

    require_once(dirname(__FILE__) . '/documentparser.php');

    class WikiWidget {

        // Passed through from extension
        var $input;
        var $argv;
        var $parser;
        var $info;
        
        var $output;
        var $debug;
        var $document;

        function __construct() {
            
        }

        // Mediawiki's XML parser doesn't allow attribute names that don't
        // match ^\w+$ so we allow additional attributes to be specified in
        // the xargs attribute.
        function _unpackArgs($args) {
            if (isset($args['xargs'])) {
                $xargs = $args['xargs'];
                unset($args['xargs']);
                $args = array_merge($this->parseQuery($xargs), $args);
            }
            return $args;
        }

        function bind($input, $argv, &$parser, &$info) {
            $this->input  =  $input;
            $this->argv   =  $this->_unpackArgs($argv);
            $this->parser =& $parser;
            $this->info   =& $info;
            
            $this->complete();
        }
        
        function complete() {
            $this->clearOutput();

            $this->setDefaults(array(
                'debug'     => 0
            ));

            $this->initialize();

            $this->dumpArgs();
        }
        
        function dumpArgs() {
            if ($this->isDebug()) {
                $this->debug("Args:\n");
                foreach($this->argv as $n => $v) {
                    $this->debug(sprintf("  %-10s = %s\n", $n, $v));
                }
            }
        }
        
        function dumpOutput() {
            if ($this->isDebug()) {
                $this->debug("Output:\n");
                foreach($this->output as $line) {
                    $this->debug($line);
                }
            }
        }
        
        //////////////////////////////////////////////////////////////////////
        // Likely candidates for subclassing                                //
        //////////////////////////////////////////////////////////////////////
        
        protected function initialize() {
            
        }

        function meta() {
            return array(
                'name'          => 'Widget Support',
                'description'   => 'Display Embedded Widgets',
                'author'        => 'Andy Armstrong',
                'url'           => 'http://hexten.net',
                'version'       => '0.1'
            );
        }
        
        function render() {
            $this->startBox('Output from ' . $this->name(), 'output');

            $this->output(
                "If you're seeing this you should probably subclass the render() method."
            );

            $this->endBox();
        }

        //////////////////////////////////////////////////////////////////////
        // Less likely candidates for subclassing                           //
        //////////////////////////////////////////////////////////////////////
        
        function name() {
            $meta = $this->meta();
            return $meta['name'] ? $meta['name'] : 'Unnamed Widget';
        }
        
        function args() {
            return $this->argv;
        }

        function renderContent() {
            try {
                $this->render();
                $this->dumpOutput();
            }
            catch (Exception $e) {
                $this->clearOutput();
                $this->renderErrors(array($e->getMessage()));
            }
        }

        // Render any errors
        function renderErrors($errs) {
            $this->startBox('Errors from ' . $this->name(), 'error');

            $this->output(
                "The following errors occurred:<br />\n"
            );

            foreach ($errs as $msg) {
                $this->output($this->tag('p', htmlentities($msg)));
            }

            $this->endBox();
        }

        function renderLinks($links) {
            $meta  = $this->meta();
            $links = $meta['url'];

            if (!is_array($links)) {
                $links = array($this->name() . ' Home Page' => $links);
            }
            
            $this->output(
                $this->tag('h3', 'Links'),
                $this->openTag('ul')
            );
            
            foreach ($links as $caption => $url) {
                $this->output(
                    $this->tag('li', 
                        $this->tag('a', $caption, array(
                            'href' => $url, 
                            'class' => 'external text'
                        ))
                    )
                );
            }
            
            $this->output(
                $this->closeTag('ul')
            );
        }

        function renderExample() {
            $meta    = $this->meta();
            $example = $meta['example'];

            if (!isset($example)) {
                $example = array();
            }
            
            // Got a list of parameters
            if (is_array($example)) {
                $args = array('type' => $this->type());
                foreach($example as $n => $v) {
                    $args[$n] = wxLiteral($this->tag('i', $v));
                }
                $example = $this->openTag('widget', $args) . "\n"
                         . $this->closeTag('widget');
            }
            
            $example = htmlentities($example);
            wxDecodeLiterals($example);
            
            $this->output(
                $this->tag('h3', 'Example'),
                $this->tag('pre', $example)
            );
        }

        // Render an about box
        function renderAbout() {
            $meta = $this->meta();
            $exclude = array('name', 'example');
            foreach ($exclude as $ex) {
                unset($meta[$ex]);
            }
            $this->output($this->openTag('table'));
            $links = $meta['url']; unset($meta['url']);
            foreach ($meta as $n => $v) {
                $this->output(
                    $this->openTag('tr'), 
                    $this->tag('td', $this->tag('b', ucfirst($n))), 
                    $this->tag('td', $v),
                    $this->closeTag('tr'), "\n"
                );
            }
            $this->output($this->closeTag('table'));
            $this->renderExample();
            $this->renderLinks($links);
        }
        
        function renderDebug() {
            $this->startBox('Debug information from ' . $this->name(), 'debug');
            if ($this->debug) {
                $this->output($this->tag('pre', htmlentities(join('', $this->debug))));
            }
            $this->endBox();
        }
        
        function startBox($caption, $type) {
            // TODO: Use MediaWiki styles
            switch ($type) {
                case 'error':
                    $border_width  = 2;
                    $border_colour = 'red';
                    break;
                    
                default:
                    $border_width  = 1;
                    $border_colour = 'gray';
                    break;
            }

            $style = "border: ${border_width}px solid ${border_colour}; "
                   . "padding: 4px; "
                   . "margin: 4px";

            $this->output(
                $this->openTag('div', array('style' => $style)),
                $this->tag('h3', htmlentities($caption))
            );
        }
        
        function endBox() {
            $this->output(
                $this->closeTag('div')
            );
        }
        
        //////////////////////////////////////////////////////////////////////
        // Helpers                                                          //
        //////////////////////////////////////////////////////////////////////

        function clearOutput() {
            $this->output = array();
        }
        
        function getOutput() {

            if ($this->isDebug()) {
                $this->renderDebug($this->debug);
                $this->debug = null;
            }

            $text = join('', $this->output);
            $this->clearOutput();
            return $text;
        }

        function output() {
            $args = func_get_args();
            $this->output[] = join('', $args);
        }

        function setDefaults($ar) {
            $this->argv = array_merge($ar, $this->argv);
        }
        
        function arg($name, $default = null) {
            if (isset($this->argv[$name])) {
                return $this->argv[$name];
            }
            return $default;
        }
        
        function need($name) {
            if (!isset($this->argv[$name])) {
                throw new Exception("Mandatory argument $name not supplied");
            }
            return $this->argv[$name];
        }
        
        function config($name, $default = null) {
            $per_host = $this->info['config']['per_host'];
            if ($per_host) {
                $host = preg_replace('/^www[.]/', '', $_SERVER['HTTP_HOST']);
                $val = $per_host[$host][$name];
                if (!is_null($val)) {
                    return $val;
                }
            }
            
            $val = $this->info['config'][$name];
            return is_null($val) ? $default : $val;
        }
        
        function needConfig($name) {
            $val = $this->config($name);
            if (is_null($val)) {
                throw new Exception("Mandatory config key $name not defined");
            }
            return $val;
        }

        function widgetDir() {
            return $this->info['home'];
        }
        
        function type() {
            return $this->info['type'];
        }
        
        function fountain() {
            return wxFountain($this->type());
        }
        
        function requireMyScript($name, $extra = '') {
            $this->requireScript($this->widgetDir() . '/' . $name, $extra);
        }
        
        function isDebug() {
            return $this->arg('debug');
        }
        
        function debug() {
            if ($this->isDebug()) {
                if (!$this->debug) {
                    $this->debug = array();
                }
                $args = func_get_args();
                $this->debug[] = join('', $args);
            }
        }
        
        function debugDump($name, $val) {
            if ($this->isDebug()) {
                $this->debug("$name:\n");
                ob_start();
                var_dump($var);
                $dump = ob_get_contents();
                ob_end_clean();
                $this->debug($dump);
            }
        }
        
        function input() {
            return $this->input;
        }
        
        function inputDocument() {
            if (!$this->document) {
                $p = new DocumentParser;
                $doc = $p->parse('<widget>' . $this->input() . '</widget>');
                $this->document = $doc['nodes'][0];
            }
            
            return $this->document;
        }
        
        function &parser() {
            return $this->parser;
        }
        
        function disableCache() {
            $this->parser->disableCache();
        }
        
        function title() {
            return $this->parser->mTitle;
        }
        
        //////////////////////////////////////////////////////////////////////
        // Wrappers around wx* functions                                    //
        //////////////////////////////////////////////////////////////////////
        
        // TODO: Deprecate wx and move the implementation here

        function home()             { return wxHome();          }
        function extensions()       { return wxExtensions();    }
        
        function requireScript($src, $extra = '') {
            $this->output(wxRequireScript($src, $extra));
        }

        function requireSupportScript() {
            $this->output(wxScriptSupport());
        }

        function parseQuery($qs) {
            return wxParseQuery($qs);
        }

        function makeURI($base, $args = null) {
            return wxMakeURI($base, $args);
        }
    
        function openTag($name, $args = null) {
            return wxOpenTag($name, $args);
        }
        
        function closeTag($name) {
            return wxCloseTag($name);
        }
        
        function closedTag($name, $args = null) {
            return wxClosedTag($name, $args);
        }

        function openScript() {
            return wxOpenScript();
        }

        function closeScript() {
            return wxCloseScript();
        }
        
        function script($src) {
            return wxScript($src);
        }

        function parseWikiMarkup($text) {
            return wxParseWikiMarkup($text, $this->parser);
        }
        
        function tag($name, $text, $args = null) {
            if (is_array($text)) {
                $out = array();
                foreach ($text as $t) {
                    $out[] = $this->tag($name, $t, $args);
                }
                return join('', $out);
            }
            else {
                return $this->openTag($name, $args) . $text . $this->closeTag($name);
            }
        }
    }

?>
