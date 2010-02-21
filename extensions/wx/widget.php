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

    require_once('common.php');

    $wxVersion  = '0.3';

    $wxRegistry      = array();
    $wxNextId        = array();
    $wxCurrentWidget = null;

    $wgExtensionFunctions[] = 'wfWidget';
    $wgExtensionCredits['parserhook'][] = array(
        'name'          => 'Embedded Widgets',
        'description'   => 'Display Embedded Widgets',
        'author'        => 'Andy Armstrong',
        'url'           => 'http://hexten.net'
    );

    function wfWidget() {
        global $wgParser;
        $wgParser->setHook('widget', 'renderWidget');
    }

    function wxAddWidget($type, $desc) {
        global $wxRegistry;
        $wxRegistry[$type] = $desc;
    }
    
    function wxWidgetInfo($type) {
        global $wxRegistry;
        $info = $wxRegistry[$type];
        if (!$info) {
            throw new Exception('Unknown widget type: ' . $type);
        }
        $info['type'] = $type;
        // Patch up home directory
        $info['home'] = wxExtensions() . '/' . wxWidgetDir($type);
        return $info;
    }

    function wxWidgetDir($type) {
        if (!preg_match('/^\w+$/', $type)) {
            throw new Exception('Bad type name: ' . $type);
        }

        return "widget/$type";
    }

    function wxWidgetForType($type) {
        global $wxRegistry;

        $src = dirname(__FILE__) . '/' . wxWidgetDir($type) . '/init.php';
        // 
        // if (!preg_match('/^\w+$/', $type)) {
        //     throw new Exception('Bad type name: ' . $type);
        // }
        // 
        // $src = dirname(__FILE__) . "/widget/$type/init.php";
        
        // Instantiate the widget
        if (!@include_once($src)) {
            throw new Exception("Unknown widget $type (can't load $src)");
        }
        
        $info   = wxWidgetInfo($type);
        $class  = $info['class'];
        $widget = new $class();

        return $widget;
    }
    
    function wxFountain($name = 'widget') {
        global $wxNextId;
        if (!isset($wxNextId[$name])) {
            $wxNextId[$name] = 1;
        }
        return $name . $wxNextId[$name]++;
    }
    
    function wxCurrentWidget() {
        global $wxCurrentWidget;
        return $wxCurrentWidget;
    }

    function renderWidget($input, $argv, &$parser) {
        global $wxRegistry, $wxCurrentWidget;

        // Localize
        $oldWidget = $wxCurrentWidget;

        $output   = '';
        $type     = $argv['type'];
        $disabled = $argv['disabled'];

        if ($disabled) {
            return '';
        }

        require_once('wikiwidget.php');
            
        try {
            if (is_null($type)) {
                $type = 'index';
                // throw new Exception('No widget type specified. Use <widget type="widget-type" />');
            }
            
            $wxCurrentWidget = wxWidgetForType($type);
            $info            = wxWidgetInfo($type);

            $wxCurrentWidget->bind($input, $argv, $parser, $info);
            
            if ($argv['about']) {
                $wxCurrentWidget->renderAbout();
            }
            else {
                $wxCurrentWidget->renderContent();
            }
            
            $output = $wxCurrentWidget->getOutput();
        }
        catch (Exception $e) {
            $output = wxFormatErrors(array($e->getMessage()));
        }

        $wxCurrentWidget = $oldWidget;
        
        return wxLiteral( $output );
    }
    
?>
