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

	$wgParser->setHook( "source", "renderSource" );
}

function syntaxHighlightingCapitalize( $language ) {
	$capitalized = ucfirst($language);
	if (!strcasecmp($capitalized, "javascript"))
		$capitalized = "JScript";
	return $capitalized;
}

function renderSource( $input, $argv, $parser ) {
	global $wgScriptPath;
	$extDir = $wgScriptPath . '/extensions/SyntaxHighlight_JS/';
	$shDir = $extDir;
	$styleDir = $shDir . 'styles/';
	$scriptDir = $shDir . 'scripts/';
	$thisPath = dirname(__FILE__);

	$text = '<pre';
	if ( isset( $argv['lang'] ) ) {
		if (!file_exists($thisPath . '/scripts/shBrush' . syntaxHighlightingCapitalize($argv['lang']) . '.js')) {
			$text = '';
			static $missing = array();
			if (!in_array($argv['lang'], $missing)) {
				$missing[$argv['lang']] = 1;
				$text = '<script>alert("No syntax-highlighting for language ' . urlencode($argv['lang']) . '");</script>';
			}
			return $text . '<pre>' . trim($input) . '</pre>';
		}
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

		foreach ( array( 'XRegExp', 'shCore' ) as $basename )
			$head .= '<script src="' . $scriptDir . $basename . '.js" type="text/javascript"></script>' . "\n";
		$head .= '<script type="text/javascript">
SyntaxHighlighter.defaults["pad-line-numbers"] = 3;
SyntaxHighlighter.defaults["toolbar"] = false;
</script>';
		$parser->mOutput->addHeadItem($head);

		$text .= '<script type="text/javascript">SyntaxHighlighter.all();</script>';
		$initialized = true;
	}
	if ( isset($argv['lang']) && !in_array($argv['lang'], $initializedLanguages) ) {
		$capitalized = syntaxHighlightingCapitalize($argv['lang']);
		$parser->mOutput->addHeadItem( '<script src="' . $scriptDir . 'shBrush' . $capitalized . '.js" type="text/javascript"></script>' . "\n" );
		$initializedLanguages[] = $argv['lang'];
	}

	return $text;
}
?>
