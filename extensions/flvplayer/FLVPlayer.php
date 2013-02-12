<?php

/*
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USAw
 *  
 * @author Christopher Ottley <cottley at gmail dot com>
 * @version 1.00
 *
 * heavily adapted by Johannes Schindelin (uses flashembed.js now)
 * 
 * Changelog
 * =========
 *
 * 1.00 - Initial release
 * 
 */

// Extension credits that show up on Special:Version
$wgExtensionCredits['parserhook'][] = array(
		'name' => 'FLVPlayer',
		'author' => 'Christopher Ottley',
		'url' => 'http://www.mediawiki.org/wiki/Extension:FLVPlayer',
		'description' => 'Allows the display of flv movies within a wiki using the FlowPlayer FLV movie player.'
		);

$wgExtensionFunctions[] = "wfFlvPlayerExtension";


/*
 * The FlvPlayer class generates code that embeds a flash movie player
 * with reference to the uploaded movie.
 *
 * The flash based flv player used is flowplayer (http://flowplayer.sourceforge.net/)
 *
 */
class FlvPlayer {

	/* Constructor */
	function FlvPlayer( $input, $argv ) {
		global $wgScriptPath;

		$this->file = trim($input);

		$this->width = $argv["width"];

		$this->height = $argv["height"];

		$this->id = $argv["id"];

		if ($this->width == "") { $this->width = "100"; }

		if ($this->height == "") { $this->height = "100"; }

		if ($this->id == "") { $this->id = "FlowPlayer"; }

		if ($argv["autoplay"] != "") {
			$this->flashvars .= "&autoPlay=" . $argv["autoplay"];
		}

		if ($argv["autobuffering"] != "") {
			$this->flashvars .= "&autoBuffering=" . $argv["autobuffering"];
		}

		if ($argv["bufferlength"] != "") {
			$this->flashvars .= "&bufferLength=" . $argv["bufferlength"];
		}

		if ($argv["loop"] != "") {
			$this->flashvars .= "&loop=" . $argv["loop"];
		} else {
			$this->flashvars .= "&loop=false";
		}

		if ($argv["progressbarcolor1"] != "") {
			$this->flashvars .= "&progressBarColor1=" . $argv["progressbarcolor1"];
		}

		if ($argv["progressbarcolor2"] != "") {
			$this->flashvars .= "&progressBarColor2=" . $argv["progressbarcolor2"];
		}

		if ($argv["videoheight"] != "") {
			$this->flashvars .= "&videoHeight=" . $argv["videoheight"];
		}

		if ($argv["hidecontrols"] != "") {
			$this->flashvars .= "&hideControls=" . $argv["hidecontrols"];
		}

		if ($argv["hideborder"] != "") {
			$this->flashvars .= "&hideBorder=" . $argv["hideborder"];
		}

		$this->flowplayerpath = $wgScriptPath . "/extensions/flvplayer/";

	}


	/* Generate final code */
	function render() {
		$isUrl = ( strstr($this->file,'http://') == $this->file );
		if($isUrl){
			$this->url = $this->file;
		}else{
			$this->url = $this->getViewPath($this->file);
		}

		$this->code = '<script type="text/javascript" src="'
			. $this->flowplayerpath . '/flashembed.js"></script>'
			. '<script>flashembed("' . $this->id . '", {'
			. 'src:"' . $this->flowplayerpath
			. 'FlowPlayer.swf", width: ' . $this->width
			. ', height: ' . $this->height
			. '}, {'
			. 'config: {autoPlay: false, autoBuffering: true,'
			. 'controlBarBackgroundColor:"0x2e8860",'
			. 'initialScale: "scale",'
			. 'videoFile: "' . $this->url . '"}});'
			. '</script>'
			. '<div id="' . $this->id . '"></div>'
			. '<a href="' . $this->url . '">Download movie</a>. '
			. '&nbsp; This movie was brought to you by '
			. '<a href=http://flowplayer.sourceforge.net>'
			. 'FlowPlayer</a>';
 
		return $this->code;
	}

	function getViewPath($file) {
		$title = File::normalizeTitle($file);
		$img = wfFindFile($title);
		if ($img == null)
			return null;

		$path = $img->getViewURL(false);

		return $path;
	}
}

function wfFlvPlayerExtension() {
	global $wgParser;

	$wgParser->setHook( "flvplayer", "renderFlvPlayer" );
}

function renderFlvPlayer( $input, $argv ) {
	// Constructor
	$flvPlayerFile = new FlvPlayer( $input, $argv );

	$result = $flvPlayerFile->render();

	return $result; // send the final code to the wiki
}

