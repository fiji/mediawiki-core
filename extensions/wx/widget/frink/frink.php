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

    class FrinkWidget extends WikiWidget {
        function meta() {
            return array(
                'name'          => 'Frink Widget',
                'description'   => 'Embed Frink',
                'author'        => 'Andy Armstrong',
                'url'           => array(
                    'Frink Wiki Widget' =>
                    'http://hexten.net/wiki/index.php?title=Frink_Wiki_Widget'
                ),
                'version'       => '0.1',
                'example'       => array(
                    'width' => 'field width',
                ),
            );
        }

        protected function initialize() {
            parent::initialize();
            $this->setDefaults(array(
                'width'     => 40,
            ));
        }
        
        function render() {
            $width = $this->arg('width');
            # Oooh look: Lisp in PHP
            $this->output(
                $this->tag('form',
                    $this->tag('table',
                        $this->tag('tr', array(
                            $this->tag('td', array(
                                'From:',
                                $this->closedTag('input', array(
                                    'type' => 'text', 
                                    'name' => 'fromVal', 
                                    'id'   => 'fromVal', 
                                    'size' => $width
                                ))
                            )),
                            $this->tag('td', array(
                                'To:',
                                $this->closedTag('input', array(
                                    'type' => 'text', 
                                    'name' => 'toVal', 
                                    'id'   => 'toVal', 
                                    'size' => $width
                                ))
                            )),
                            $this->tag('td',
                                $this->closedTag('input', array(
                                    'type' => 'submit',
                                    'value' => 'Calculate'
                                )),
                                array('colspan' => 2)
                            ),
                        ))
                    ), 
                    array(
                        'action' => 'http://futureboy.us/fsp/frink.fsp', 
                        'method' => 'get'
                    )
                ),
                $this->tag('p',
                    $this->tag('a', 'Frink documentation', array(
                        'href' => 'http://futureboy.us/frinkdocs/'
                    ))
                )
            );
        }
    }
?>
