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
		'name' => 'ListNews',
		'author' => 'Johannes Schindelin',
		'description' => 'List News directly, without going via RSS feed'
		);

$wgExtensionFunctions[] = "wfListNewsExtension";

function wfListNewsExtension() {
	global $wgParser;

	$wgParser->setHook( "listnews", "renderListNews" );
}

function renderListNews( $input, $argv, $parser ) {
	$parser->disableCache();
	$limit = isset($argv['limit']) ? $argv['limit'] : 0;
	if ($limit == 0)
		$limit = 5;

	$dbr = wfGetDB( DB_SLAVE );
	$res = $dbr->select(
			array( 'page', 'categorylinks' ),
			array( 'page_title', 'page_namespace' ),
			array( 'cl_from          =  page_id',
			       'cl_to = "News"' ),
			__METHOD__,
			array( 'ORDER BY' => 'page_title DESC',
					'LIMIT'    => $limit ) );

	$count = 0;
	$text = '<ul>';
	while( $x = $dbr->fetchObject ( $res ) ) {
		$title = Title::makeTitle( $x->page_namespace, $x->page_title );
		$text .= '<li><a href="' . $title->getLocalURL()
			. '">' . $title->getText() . "</a></li>\n";
	}
	$dbr->freeResult( $res );
	$text .= "</ul>\n";

	return $text;
}
?>
