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
 * @author Johannes Schindelin
 *
 */

$wgExtensionCredits['parserhook'][] = array(
		'name' => 'FijiRandomSnapshot',
		'author' => 'Johannes Schindelin',
		'description' => 'Show a random image from Fiji:FeaturedProjects'
		);

$wgExtensionFunctions[] = "wfFijiRandomSnapshotExtension";

function splitProject($project) {
	if ($project == '')
		return '';
	$project = explode('|', $project, 2);
	$name = htmlspecialchars($project[0]);
	if (substr($name, 0, 2) == '* ')
		$name = substr($name, 2);
	$project = explode("\n", $project[1], 2);
	$img = wfFindFile($project[0])->getViewURL();
	$description = htmlspecialchars($project[1]);
	return array($name, $img, $description);
}

function getFijiRandomSnapshot() {
	$title = Title::newFromText($GLOBALS['wgSitename'] . ':Featured_Projects');
	$revision = Revision::newFromTitle($title);
	if ($revision == null)
		return null;
	$text = $revision->getRawText();
	$projects = explode("\n\n", $text);
	$index = rand(0, count($projects) - 1);
	$project = $projects[$index];
	return splitProject($project);
}

function getAllSnapshots() {
	$title = Title::newFromText($GLOBALS['wgSitename'] . ':Featured_Projects');
	$revision = Revision::newFromTitle($title);
	if ($revision == null)
		return null;
	$text = $revision->getRawText();
	$projects = explode("\n\n", $text);
	$result = array();
	foreach ($projects as $project)
		array_push($result, splitProject($project));
	return $result;
}

function renderProject($parser, $array, $width = 400) {
	$name = $array[0];
	$img = $array[1];
	$description = $array[2];

	$desc_plain = str_replace('[[', '',
			str_replace(']]', '', $description));
	$desc_link = $parser->recursiveTagParse($description);
	$title = Title::makeTitle('', $name);
	return '<table border="0" width="' . $width . '"><tr><td>'
		. '<a href="' . $title->getLocalURL()
		. '" class="image" title="' . $desc_plain
		. '"><img alt="' . $desc_plain . '" src="' . $img
		. '" width="' . $width . '" /></a></td></tr><tr><td>'
		. $desc_link . "</td></tr></table>";
}

function wfFijiRandomSnapshotExtension() {
	global $wgParser;

	$wgParser->setHook( "fijirandomsnapshot", "renderFijiRandomSnapshot" );
}

function renderFijiRandomSnapshot( $input, $argv, $parser ) {
	$parser->disableCache();
	if (isset($argv['all'])) {
		$array = getAllSnapshots();
		if ($array == null)
			return 'Please edit <span style="color:red;">Fiji:Featured_Projects</span>';
		$result = '<table border="0"><tr><th colspan="2"><i>Featured Projects</i></th>';
		$counter = 0;
		foreach ($array as $project) {
			if (($counter & 1) == 0)
				$result .= '</tr><tr>';
			$result .= '<td>' . renderProject($parser, $project, 350)
				. '</td>';
			$counter++;
		}
		$result .= '</tr></table>';
		return $result;
	}
	$array = getFijiRandomSnapshot();
	if (!isset($array))
		return '';
	return '<div class="floatright" style="background:#ffffffff; background-color:#ffffffff;"><span>' . renderProject($parser, $array)
		. "</span></div>\n";
}
