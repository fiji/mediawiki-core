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

    class ExternalCmd {
        var $cmd;       // Command
        var $args;      // Args array
        var $err_tmp;   // Name of temporary file for STDERR
        var $stdout;    // Output from command
        var $stderr;    // Error output from command
        var $input;     // Input to pipe to command
        
        function __construct($cmd, $args) {
            $this->cmd     = $cmd;
            $this->args    = $args;
            $this->err_tmp = null;
            $this->input   = null;
        }
        
        function __destruct() {
            $this->cleanup();
        }

        private function cleanup() {
            if (!is_null($this->err_tmp)) {
                //unlink($this->err_tmp);
                $this->err_tmp = null;
            }
        }

        private function tempFile() {
            # TODO: Fixme
            return tempnam('/tmp', 'wx-');
        }

        function run() {
            $this->stdout = null;
            $this->stderr = null;

            $inp_tmp      = null;

            $cl = array( $this->cmd );
            foreach ($this->args as $arg) {
                $cl[] = escapeshellarg($arg);
            }
            
            $this->err_tmp = $this->tempFile();
            $cl[] = "2> $this->err_tmp";
            
            if (!is_null($this->input)) {
                $inp_tmp = $this->tempFile();
                file_put_contents($inp_tmp, $this->input);
                $cl[] = "< $inp_tmp";
            }
            
            $cmdline = join(' ', $cl);
            $this->stdout = shell_exec($cmdline);
            
            if (!is_null($inp_tmp)) {
                //unlink($inp_tmp);
            }

            # Fake RC for now
            $rc = 0;
            if (filesize($err_tmp) > 0) {
                $rc = 1;
            }

            return $rc;
        }

        function setInput($input) {
            $this->input = $input;
        }
        
        // Get whatever command sent to STDOUT
        function getOutput() {
            return $this->stdout;
        }
        
        // Get whatever command sent to STDERR
        function getErrorOutput() {
            if (is_null($this->stderr)) {
                if (!is_null($this->err_tmp) && file_exists($this->err_tmp)) {
                    $this->stderr = file_get_contents($this->err_tmp);
                    $this->cleanup();
                }
                else {
                    $this->stderr = '';
                }
            }
            
            return $this->stderr;
        }
    }

?>