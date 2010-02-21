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

    require_once(dirname(__FILE__) . '/graphviz.php');

    wxAddWidget('graphviz', array(
        'class'  => 'GraphVizWidget',
        'config' => array(
            'cache' => '/wiki/images',
            'per_host' => array(
                'hexten.voodoo.ripley' => array(
                    'helper' => array(
                        'dot'   => '/sw/bin/dot',
                        'neato' => '/sw/bin/neato',
                        'twopi' => '/sw/bin/twopi',
                        'circo' => '/sw/bin/circo',
                        'fdp'   => '/sw/bin/fdp',
                    ),
                ),
            ),
            'helper' => array(
                'dot'   => '/usr/bin/dot',
                'neato' => '/usr/bin/neato',
                'twopi' => '/usr/bin/twopi',
                'circo' => '/usr/bin/circo',
                'fdp'   => '/usr/bin/fdp',
            ),
        )
    ));

?>
