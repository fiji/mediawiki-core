<?php
/**
* News Channel extension 1.52
* This MediaWiki extension represents a RSS 2.0/Atom 1.0 news channel for wiki project.
* 	The channel is implemented as a dynamic [[Special:NewsChannel|special page]].
* 	All pages from specified category (e.g. "Category:News") are considered
* 	to be articles about news and published on the site's news channel.
* Extension setup file.
* Requires MediaWiki 1.8 or higher.
* Extension's home page: http://www.mediawiki.org/wiki/Extension:News_Channel
*
* Copyright (c) Moscow, 2008, Iaroslav Vassiliev  <codedriller@gmail.com>
* Distributed under GNU General Public License 2.0 or later (http://www.gnu.org/copyleft/gpl.html)
*/

if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/NewsChannel/NewsChannel.php" );
EOT;
	exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'News Channel',
	'version' => 1.52,
	'author' => 'Iaroslav Vassiliev <codedriller@gmail.com>',
	'description' => 'This MediaWiki extension represents a news channel for wiki project. ' .
		'The channel is implemented as a dynamic [[Special:NewsChannel|special page]].',
	'url' => 'http://www.mediawiki.org/wiki/Extension:News_Channel'
);

$wgExtensionFunctions[] = 'wfSetupNewsChannelExtension';

$dir = dirname( __FILE__ ) . '/';
$wgAutoloadClasses['NewsChannel'] = $dir . 'NewsChannel_body.php';
$wgExtensionMessagesFiles['NewsChannel'] = $dir . 'NewsChannel.i18n.php';
$wgExtensionMessagesFiles['NewsChannelAlias'] = $dir . 'NewsChannel.alias.php';
$wgSpecialPages['NewsChannel'] = 'NewsChannel';

function wfSetupNewsChannelExtension() {
	global $IP, $wgMessageCache, $wgOut;

	$title = Title::newFromText( 'NewsChannel', NS_SPECIAL );
	$wgOut->addLink( array(
		'rel' => 'alternate',
		'type' => 'application/rss+xml',
		'title' => wfMsg( 'newschannel' ) . ' - RSS 2.0',
		'href' => $title->getLocalURL( 'format=rss20' ) ) );
	$wgOut->addLink( array(
		'rel' => 'alternate',
		'type' => 'application/atom+xml',
		'title' => wfMsg( 'newschannel' ) . ' - Atom 1.0',
		'href' => $title->getLocalURL( 'format=atom10' ) ) );

	return true;
}
?>
