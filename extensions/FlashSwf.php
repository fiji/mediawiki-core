<?php
/**
 * MediaWiki Flash SWF extension
 * set up MediaWiki to react to the "<swf>" tag
 *
 * @version 0.2
 * @author Brigitte Jellinek
 */
 
/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if (!defined('MEDIAWIKI')) die();
 
//Avoid unstubbing $wgParser too early on modern (1.12+) MW versions, as per r35980
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'wfSwf';
} else {
	$wgExtensionFunctions[] = 'wfSwf';
}
 
$wgExtensionCredits['parserhook'][] = array(
	'name' => 'Flash SWF',
	'version' => '0.2',
	'author' => 'Brigitte Jellinek',
	'description' => 'Allows the display of flash movies within a wiki with the <tt>&lt;swf&gt;</tt> tag',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Flash_swf',
);
 
function wfSwf() {
        global $wgParser;
        $wgParser->setHook( 'swf', 'renderSwf' );
	return true;
}
 
function renderSwf( $input, $argv ) {
	global $wgScriptPath;
	$output = "";
 
	#parse fields in flashow-section
	$fields = explode("|",$input);
	$input = $fields[0];
 
	//added functionality for parameters passed within the tag's body
	//<swf>movie.swf|width=200|height=300|loop=false</swf>
	for ($i=1; $i < sizeof($fields); $i++) {
		$newArg = explode("=", $fields[$i]);
		$argv[$newArg[0]] = $newArg[1];
	}
 
	// external URL
	if ( (strpos($input , "http") === 0) && 
		 (strpos($input, ".swf") == strlen($input)-4)
		) {
		$url = $input;
	}
 
	// internal media:
	else {
                $title = File::normalizeTitle($input);
                $img = wfFindFile($title);
                $path = $img->getViewURL(false);
		if ( ! $path ) return "No path for internal Media:$input";
		$dir = dirname($_SERVER['SCRIPT_FILENAME']);
		$url = str_replace($dir, $wgScriptPath, $path );
	}
 
	if (isset($argv['width'])) {
		$width = $argv['width'];
		if (strpos($width,"%") == (strlen($width) - 1)) {
			$divWidth = "width:$width";
		} else {
			$divWidth = "width:$width"."px";
		}
		$width = 'width="' . $width . '"';
	} else {
		$divWidth = "";
		$width = '';
	}
 
	if (isset($argv['height'])) {
		$height = $argv['height'];
		if (strpos($height,"%") == (strlen($height) - 1)) {
			$divHeight = "height:$height";
		} else {
			$divHeight = "height:$height"."px";
		}
		$height = 'height="' . $height . '"';
	} else {
		$divHeight = "";
		$height = '';
	}
 
	$id = basename($input, ".swf");
	$output  .=<<<EOM
<!-- display a swf -->
<div class="swf" style="$divWidth;$divHeight">
<object
	classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
	codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0"
	$width $height id="$id" align="middle">
<param name="allowScriptAccess" value="sameDomain" />
<param name="menu" value="true" />
<param name="movie" value="$url" />
<param name="quality" value="high" />
<param name="bgcolor" value="#ffffff" />
<embed src="$url" quality="high" bgcolor="#ffffff"
	   $width $height
	   name="$id" align="middle" allowScriptAccess="sameDomain"
	   type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object>
</div>
<!-- end of swf display -->
EOM;
     $output = str_replace("\n", "", $output);
 
 
     return $output;
}
