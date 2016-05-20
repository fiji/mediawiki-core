<?php

/**
 * This extension implements syntax highlighting by using Sebastian Sdorra's
 * fork of Alex Gorbatchev's SyntaxHighlighter in JavaScript:
 *
 *	https://bitbucket.org/sdorra/syntaxhighlighter
 */

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
 * @author Johannes Schindelin
 *
 */

$wgExtensionCredits['parserhook'][] = array(
		'name' => 'SyntaxHighlight_JS',
		'author' => 'Johannes Schindelin',
		'description' => "Render source purely using javascript, using Alex Gorbatchev's SyntaxHighlighter with Sebastian Sdorra's fixes"
		);

$wgExtensionFunctions[] = "wfSyntaxHighlightJSExtension";

function wfSyntaxHighlightJSExtension() {
	global $wgParser;

	$wgParser->setHook( "source", "wfSyntaxHighlightingRender" );
}

function wfSyntaxHighlightingCapitalize( $language ) {
	$capitalized = ucfirst($language);
	if (!strcasecmp($capitalized, "javascript"))
		$capitalized = "JScript";
	return $capitalized;
}

function wfSyntaxHighlightingMissing( $language ) {
	$thisDir = dirname(__FILE__);
	if (file_exists($thisDir . '/scripts/shBrush' . wfSyntaxHighlightingCapitalize($language) . '.js'))
		return false;

	if (!isset($_POST['wpPreview']))
		return '';
	static $missing = array();
	if (in_array($argv['lang'], $missing))
		return '';
	$missing[$argv['lang']] = 1;
	static $all;
	if (!isset($all)) {
		$dir = opendir($thisDir . '/scripts');
		while (($entry = readdir($dir)) !== false) {
			$name = preg_replace('~shBrush(.*)\.js~', '$1', $entry);
			if ($name == $entry)
				continue;
			if (isset($all))
				$all .= ', ';
			else
				$all = '';
			$all .= $name;
		}
		closedir($dir);
	}
	return '<script>alert("No syntax-highlighting for language ' . urlencode($language) . '\n\nAvailable: ' . $all . '");</script>';
}

function wfSyntaxHighlightingRender( $input, $argv, $parser ) {
	global $wgScriptPath;
	$extDir = $wgScriptPath . '/extensions/SyntaxHighlight_JS/';
	$shDir = $extDir;
	$styleDir = $shDir . 'styles/';
	$scriptDir = $shDir . 'scripts/';

	$text = '<pre';
	if ( isset( $argv['lang'] ) ) {
		$missing = wfSyntaxHighlightingMissing( $argv['lang'] );
		if ($missing !== false)
			return $missing . '<pre>' . $input . '</pre>';
		$text .= ' class="brush:' . $argv['lang'] . '"';
	}
	$text .= ">\n";
	$text .= str_replace(array('<', '>'), array('&lt;', '&gt;'), trim($input));
	$text .= "</pre>\n";

	static $initialized = false;
	static $initializedLanguages = array();
	if ( ! $initialized ) {
		$head = '<link type="text/css" rel="stylesheet" href="' .  $styleDir . "shCoreDjango.css\"/>\n";
		$head .= '<link type="text/css" rel="stylesheet" href="' .  $styleDir . "shThemeFiji.css?32\"/>\n";

		$head .= '<script src="' . $scriptDir . 'shCore.js" type="text/javascript"></script>' . "\n";
		$head .= '<script type="text/javascript">
SyntaxHighlighter.defaults["pad-line-numbers"] = 3;
SyntaxHighlighter.defaults["toolbar"] = false;
</script>';
		$parser->mOutput->addHeadItem($head);

		$text .= '<script type="text/javascript">SyntaxHighlighter.all();</script>';
		$initialized = true;
	}
	if ( isset($argv['lang']) && !in_array($argv['lang'], $initializedLanguages) ) {
		$capitalized = wfSyntaxHighlightingCapitalize($argv['lang']);
		$parser->mOutput->addHeadItem( '<script src="' . $scriptDir . 'shBrush' . $capitalized . '.js" type="text/javascript"></script>' . "\n" );
		$initializedLanguages[] = $argv['lang'];
	}

	return $text;
}
?>
