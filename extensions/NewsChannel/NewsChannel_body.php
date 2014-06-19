<?php
/**
* News Channel extension 1.52
* This MediaWiki extension represents a RSS 2.0/Atom 1.0 news channel for wiki project.
* 	The channel is implemented as a dynamic [[Special:NewsChannel|special page]].
* 	All pages from specified category (e.g. "Category:News") are considered
* 	to be articles about news and published on the site's news channel.
* File with extension's main source code.
* Requires MediaWiki 1.8 or higher.
* Extension's home page: http://www.mediawiki.org/wiki/Extension:News_Channel
*
* Copyright (c) Moscow, 2008, Iaroslav Vassiliev  <codedriller@gmail.com>
* Distributed under GNU General Public License 2.0 or later (http://www.gnu.org/copyleft/gpl.html)
*/

/**
 * Class manages Special:NewsChannel page and feed ouput.
 */
class NewsChannel extends SpecialPage
{
	// Set up general channel info here

	/** Channel title. */
	var $channelTitle = 'Fiji news';
	/** Channel description, preferably just one sentence. */
	var $channelDescription = 'News about Fiji, the Fatastic ImageJ distribution.'; 
	/** Link to site. */
	var $channelSiteLink = 'http://fiji.sc/';
	/** Channel's language code and optional country subcode. */
	var $channelLanguage = 'en-US';
	/** Copyright string. */
	var $channelCopyright = '';
	/**
	* Channel's logo image. In RSS 2.0 specification only JPG, GIF or PNG formats are allowed;
	* recommended default size is 88x31. In Atom 1.0 format an image should have 1:1 aspect ratio.
	* The image should be suitable for presentation at a small size.
	*/
	var $channelLogoImage = 'http://www.mywikisite.com/rssicon.png';
	/** Time in minutes before channel cache invalidation occurs. */
	var $channelUpdateInterval = '10';
	/** Default number of recent (most fresh) news to list on the channel. */
	var $channelNewsItems = '10';
	/** Name or alias of channel's editor-in-chief. */
	var $channelEditorName = 'Johannes Schindelin';
	/** E-mail of channel's editor-in-chief. */
	var $channelEditorAddress = 'schindelin@mpi-cbg.de';
	/** Name or alias of channel's webmaster. */
	var $channelWebMasterName = 'Johannes Schindelin';
	/** E-mail of channel's webmaster. */
	var $channelWebMasterAddress = 'schindelin@mpi-cbg.de';
	/** Title of category, containing news articles. */
	var $newsWikiCategory = 'News';
	/** Title of category, that must be absolutely excluded from export. */
	var $newsWikiExcludeCategory = 'Disputed';
	/** Optional prefix to remove from news article title to clean channel headlines. */
	var $newsWikiArticlePrefix = 'News/';
	/**
	* Array of names (wiki accounts) of users, allowed to publish news on the channel;
	* leave empty array() to allow everyone, or fill in names like: array( "User1", "User2", "User3" );
	*/
	var $authorizedEditors = array();
	/**
	* Wikimarkup convert/delete option. If set to true, script converts wikimarkup in news
	* to HTML markup; if set to false, basic wikimarkup is just removed (some old feed readers
	* and aggregators don't support HTML markup in news items at all).
	*/
	var $convertWikiMarkup = true;

	// End of configuration settings

	var $namespacesToRemove;
	var $shortChannelLanguage;
	var $authorizedEditorsStr = '';
	var $renderingPage;

	/**
	 * Constructor is used to initialize class member variables.
	 */
	function __construct() {
		parent::__construct( 'NewsChannel' );
		global $wgContLang, $wgCanonicalNamespaceNames;

		$this->WikiMediaPresetConfig();
		$this->GlobalVariablesConfig();

		// Links to categories, images and mediafiles will be removed from news texts later in
		// purifyTextForFeed() function, if sysop chooses to remove wiki markup completely.
		$this->namespacesToRemove =
			preg_quote( $wgContLang->getNsText(NS_CATEGORY), "/" ) . '|' . 
			$wgCanonicalNamespaceNames[NS_CATEGORY] . '|' .
			preg_quote( $wgContLang->getNsText(NS_IMAGE), "/" ) . '|' .
			$wgCanonicalNamespaceNames[NS_IMAGE] . '|' .
			preg_quote( $wgContLang->getNsText(NS_MEDIA), "/" ) . '|' .
			$wgCanonicalNamespaceNames[NS_MEDIA];

		$this->channelSiteLink .=
			( isset( $this->channelSiteLink ) && $this->channelSiteLink[ strlen( $this->channelSiteLink ) ] == '/' ) ? '' : '/';

		$this->shortChannelLanguage = strrpos( $this->channelLanguage, '-' ) === false
			? $this->channelLanguage
			: substr($this->channelLanguage, 0, strrpos($this->channelLanguage, '-'));

	}

	/**
	 * Function checks if local configuration settings are overridden by global variables
	 * in LocalSetting.php file or somewhere else.
	 */
	function GlobalVariablesConfig() {
		global $wgNewsChannelTitle, $wgNewsChannelDescription, $wgNewsChannelSiteLink,
			$wgNewsChannelLanguage, $wgNewsChannelCopyright, $wgNewsChannelLogoImage,
			$wgNewsChannelUpdateInterval, $wgNewsChannelDefaultItems, $wgNewsChannelEditorName,
			$wgNewsChannelEditorAddress, $wgNewsChannelWebMasterName, $wgNewsChannelWebMasterAddress,
			$wgNewsChannelCategory, $wgNewsChannelExcludeCategory, $wgNewsChannelRemoveArticlePrefix,
			$wgNewsChannelAuthorizedEditors, $wgNewsChannelConvertWikiMarkup;

		if( isset( $wgNewsChannelTitle ) )
			$this->channelTitle = $wgNewsChannelTitle;
		if( isset( $wgNewsChannelDescription ) )
			$this->channelDescription = $wgNewsChannelDescription;
		if( isset( $wgNewsChannelSiteLink ) )
			$this->channelSiteLink = $wgNewsChannelSiteLink;
		if( isset( $wgNewsChannelLanguage ) )
			$this->channelLanguage = $wgNewsChannelLanguage;
		if( isset( $wgNewsChannelCopyright ) )
			$this->channelCopyright = $wgNewsChannelCopyright;
		if( isset( $wgNewsChannelLogoImage ) )
			$this->channelLogoImage = $wgNewsChannelLogoImage;
		if( isset( $wgNewsChannelUpdateInterval ) )
			$this->channelUpdateInterval = $wgNewsChannelUpdateInterval;
		if( isset( $wgNewsChannelDefaultItems ) )
			$this->channelNewsItems = $wgNewsChannelDefaultItems;
		if( isset( $wgNewsChannelEditorName ) )
			$this->channelEditorName = $wgNewsChannelEditorName;
		if( isset( $wgNewsChannelEditorAddress ) )
			$this->channelEditorAddress = $wgNewsChannelEditorAddress;
		if( isset( $wgNewsChannelWebMasterName ) )
			$this->channelWebMasterName = $wgNewsChannelWebMasterName;
		if( isset( $wgNewsChannelWebMasterAddress ) )
			$this->channelWebMasterAddress = $wgNewsChannelWebMasterAddress;
		if( isset( $wgNewsChannelCategory ) )
			$this->newsWikiCategory = $wgNewsChannelCategory;
		if( isset( $wgNewsChannelExcludeCategory ) )
			$this->newsWikiExcludeCategory = $wgNewsChannelExcludeCategory;
		if( isset( $wgNewsChannelRemoveArticlePrefix ) )
			$this->newsWikiArticlePrefix = $wgNewsChannelRemoveArticlePrefix;
		if( isset( $wgNewsChannelAuthorizedEditors ) )
			$this->authorizedEditors = $wgNewsChannelAuthorizedEditors;
		if( isset( $wgNewsChannelConvertWikiMarkup ) )
			$this->convertWikiMarkup = $wgNewsChannelConvertWikiMarkup;
	}

	/**
	 * Function presets some configuration settings for WikiMedia Foundation projects.
	 */
	function WikiMediaPresetConfig() {
		global $wgSitename, $wgServer, $wgContLang;

		if( $wgSitename == 'Wikinews' ) {
			$this->channelTitle = 'Latest Wikinews Headlines';
			$this->channelDescription = 'Wikinews, the free news site you can write';	// MediaWiki:Tagline?
			$this->channelSiteLink = $wgServer;
			$this->channelLanguage = $wgContLang;
			$this->channelCopyright = 'Copyright © Wikinews contributors. Released under ' .
				'the creative commons attribution 2.5 license. See http://creativecommons.org/' .
				'licenses/by/2.5/ for more information.';	// MediaWiki:Copyright?
			$this->channelLogoImage = 'http://upload.wikimedia.org/wikipedia/commons/' .
				'thumb/8/8a/Wikinews-logo.png/88px-Wikinews-logo.png';
			$this->channelUpdateInterval = '30';
			$this->channelNewsItems = '15';
			$this->channelEditorName = 'Wikinews';
			$this->channelEditorAddress = 'wikinews-l@lists.wikimedia.org';
			$this->channelWebMasterName = 'Wikinews';
			$this->channelWebMasterAddress = 'wikinews-l@lists.wikimedia.org';
			//$this->newsWikiCategory = 'Published';		// must be set for every language individually
			//$this->newsWikiExcludeCategory = 'Disputed';		// must be set for every language individually
			$this->newsWikiArticlePrefix = '';
			$this->convertWikiMarkup = true;
			$this->authorizedEditors = array();
		}
	}

	/**
	 * This essential function is called when user requests Special:NewsChannel page or
	 * requests feed data. It also checks some configuration settings.
	 *
	 * @param string $par Custom parameters.
	 */
	function execute( $par ) {
		global $wgRequest, $wgOut, $wgVersion;

		$mediawikiVersion = explode('.', $wgVersion);
		if( intval( $mediawikiVersion[0] ) == 1 && intval( $mediawikiVersion[1] ) < 8 ) {
			$wgOut->showErrorPage( "Error: Upgrade Required", "The News Channel extension can't work " .
				"on MediaWiki older than 1.8. Please, upgrade." );
			return;
		}

		if( $this->newsWikiCategory == '' || $this->newsWikiCategory == null ) {
			$wgOut->showErrorPage( "Error: Misconfiguration", "Main category containing news articles " .
				"was not defined for News Channel extension. Please, define it." );
			return;
		}

		if( !is_null( $wgRequest->getVal( 'format' ) ) ) {
			$this->showChannel();
		} else {
			$this->showForm();
		}
	}

	/**
	 * This is extension's main function. It processes user request, queries the database
	 * and formats news items for feed. Then it outputs the feed by calling
	 * outputChannelMarkup( $newsItems ) function.
	 */
	function showChannel() {
		global $wgContLang, $wgCanonicalNamespaceNames, $wgDBprefix, $wgRequest;

		$newsItemXmlMarkup = array(
		'rss20_item' => '
		<item>
			<title>%s</title>
			<link>%s</link>
			<description>%s</description>
			<pubDate>%s</pubDate>
			<guid>%s</guid>
		</item>',
		'atom10_item' => '
		<entry>
			<title>%s</title>
			<link href="%s" />
			<content>%s</content>
			<updated>%s</updated>
			<id>%s</id>
		</entry>',
		);

		wfProfileIn( 'NewsChannel::showChannel' );

		$dbr =& wfGetDB( DB_SLAVE );

		foreach( $this->authorizedEditors as $editor )
			$this->authorizedEditorsStr .= $dbr->addQuotes( $editor ) . ',';
		$this->authorizedEditorsStr = rtrim( $this->authorizedEditorsStr, ',' );

		$limit = $wgRequest->getInt( 'limit', $this->channelNewsItems );
		if( $limit == 0 )
			$limit = $this->channelNewsItems;
		elseif( $limit > 50 )
			$limit = 50;	// this is an absolute limit, protecting site from overload
		$limit = ($limit == 0) ? $this->channelNewsItems : $limit;
		$format = $wgRequest->getVal( 'format' );
		$removeCatPrefixes = '/^(' . preg_quote( $wgContLang->getNsText( NS_CATEGORY), "/" ) .'|'.
			preg_quote( $wgCanonicalNamespaceNames[NS_CATEGORY] ) . '):/i';
		$inCategoriesStr = $dbr->addQuotes( str_replace( ' ', '_', $this->newsWikiCategory ) ) . ',';
		$inCategoriesCount = 1;
		if( $this->newsWikiExcludeCategory != '' || $this->newsWikiExcludeCategory != null ) {
			$exCategoriesStr = $dbr->addQuotes( str_replace( ' ', '_', $this->newsWikiExcludeCategory ) ) . ',';
			$exCategoriesCount = 1;
		}
		else {
			$exCategoriesStr = '';
			$exCategoriesCount = 0;
		}
		for( $i = 1; $i <= 8; $i++ ) {
			$category = trim( $wgRequest->getVal( 'cat' . $i ) );
			$category = ucfirst( preg_replace( $removeCatPrefixes, '', $category ) );
			if( $category != null && $category != '' ) {
				$inCategoriesStr .= $dbr->addQuotes( str_replace( ' ', '_', $category ) ) . ',';
				$inCategoriesCount++;
			}
			$category = trim( $wgRequest->getVal( 'excat' . $i ) );
			$category = ucfirst( preg_replace( $removeCatPrefixes, '', $category ) );
			if( $category != null && $category != '' ) {
				$exCategoriesStr .= $dbr->addQuotes( str_replace( ' ', '_', $category ) ) . ',';
				$exCategoriesCount++;
			}
		}
		$inCategoriesStr = rtrim( $inCategoriesStr, ',' );
		$exCategoriesStr = rtrim( $exCategoriesStr, ',' );

		$mySqlVersion = explode( '.', mysql_get_server_info() );
		if( intval( $mySqlVersion[0] ) < 4 ||
			(intval( $mySqlVersion[0] ) == 4 && intval( $mySqlVersion[1] ) < 1) )
				$subquerySupport = false;
		else
			$subquerySupport = true;

		$textTableName = $dbr->tableName( 'text' );
		$pageTableName = $dbr->tableName( 'page' );
		$revisionTableName = $dbr->tableName( 'revision' );
		$catlinksTableName = $dbr->tableName( 'categorylinks' );
		$newsItemsCount = 0;
		$sqlQueryStr = '';
		$exCatSqlQueryStr = '';

		if ( $subquerySupport == false ) {
			$sqlQueryStr = 
				"SELECT {$catlinksTableName}.cl_from, " .
					"MIN({$catlinksTableName}.cl_timestamp) AS timestamp, COUNT(*) AS match_count, " .
					"{$pageTableName}.page_namespace AS ns, {$pageTableName}.page_title AS title, " .
					"{$pageTableName}.page_id AS id, {$revisionTableName}.rev_user_text AS user, " .
					"{$textTableName}.old_text AS wikitext " .
				"FROM {$catlinksTableName}, {$pageTableName}, {$revisionTableName}, {$textTableName} " .
				"WHERE cl_to IN({$inCategoriesStr}) " .
					"AND {$catlinksTableName}.cl_from = {$pageTableName}.page_id " .
					"AND {$pageTableName}.page_latest = {$revisionTableName}.rev_id " .
					"AND {$revisionTableName}.rev_text_id = {$textTableName}.old_id " .
				"GROUP BY cl_from " .
				"HAVING match_count = {$inCategoriesCount} " .
				"ORDER BY match_count DESC, timestamp DESC " .
				"LIMIT " . round( intval( $limit ) * 1.5 );
			$exCatSqlQueryStr = "SELECT COUNT(*) AS count FROM {$catlinksTableName} " .
				"WHERE cl_from = %s AND cl_to IN({$exCategoriesStr})";
		}
		else {
			$accessResriction = count( $this->authorizedEditors ) == 0 ? ""
				: "AND {$revisionTableName}.rev_user_text IN ({$this->authorizedEditorsStr})";
			$exCatSqlQueryStr = ($exCategoriesCount == 0) ? '' : "AND cl_from NOT IN " .
					"(SELECT cl_from FROM {$catlinksTableName} WHERE cl_to IN({$exCategoriesStr}))";
			$sqlQueryStr =
				"SELECT {$pageTableName}.page_namespace AS ns, {$pageTableName}.page_title AS title, " .
					"{$textTableName}.old_text AS wikitext, " .
					"matches.min_timestamp AS timestamp " .
				"FROM {$pageTableName}, {$revisionTableName}, {$textTableName}, " .
					"(SELECT cl_from, MIN(cl_timestamp) AS min_timestamp, COUNT(*) AS match_count " .
					"FROM {$catlinksTableName} " .
					"WHERE cl_to IN({$inCategoriesStr}) {$exCatSqlQueryStr} " .
					"GROUP BY cl_from) AS matches " .
				"WHERE matches.match_count = {$inCategoriesCount} " .
					"AND matches.cl_from = {$pageTableName}.page_id " .
					"AND {$pageTableName}.page_latest = {$revisionTableName}.rev_id " .
					"AND {$revisionTableName}.rev_text_id = {$textTableName}.old_id " .
					"{$accessResriction} " .
				"ORDER BY timestamp DESC " .
				"LIMIT {$limit}";
		}

		$res = $dbr->query( $sqlQueryStr, 'NewsChannel::showChannel', false );
		$this->renderingPage = new OutputPage();
		$newsItems = '';
		$dbr2 =& wfGetDB( DB_SLAVE );
		while( ($row = $dbr->fetchObject( $res )) && $newsItemsCount <= $limit ) {
			if( $subquerySupport == false ) {
				if( $row->match_count != $inCategoriesCount )
					continue;
				if( count( $this->authorizedEditors ) > 0 &&
					!in_array( $row->user, $this->authorizedEditors ) )
						continue;
				if( $exCategoriesCount > 0 ) {
					$res2 = $dbr2->query( sprintf( $exCatSqlQueryStr, $row->id ),
						'NewsChannel::showChannel', false );
					$row2 = $dbr2->fetchObject( $res2 );
					$skipRow = intval( $row2->count );
					$dbr2->freeResult( $res2 );
					if( $skipRow > 0 )
						continue;
				}
			}
			$titleObj = Title::newFromText( $row->title );
			$title = $titleObj->getText();
			if( strlen( $this->newsWikiArticlePrefix ) > 0
				&& strpos( $title, $this->newsWikiArticlePrefix ) === 0)
					$title = substr( $title, strlen( $this->newsWikiArticlePrefix ) );
			$title = htmlspecialchars( $title, ENT_QUOTES );
			if( $format == 'rss20' ) {
				$newsItems .= sprintf( $newsItemXmlMarkup["rss20_item"],
					$title,	// <title>
					$titleObj->getFullURL(),	// <link>
					($this->convertWikiMarkup === true
						? $this->convertMarkupForFeedEx( $row->wikitext )
						: $this->purifyTextForFeed( $row->wikitext )),	// <description>
					$this->convertTimestamp( $row->timestamp, DATE_RSS ),	// <pubDate>
					$titleObj->getFullURL()	// <guid>
				);
			}
			elseif( $format == 'atom10' ) {
				$newsItems .= sprintf( $newsItemXmlMarkup["atom10_item"],
					$title,	// <title>
					$titleObj->getFullURL(),	// <link>
					($this->convertWikiMarkup === true
						? $this->convertMarkupForFeedEx( $row->wikitext )
						: $this->purifyTextForFeed( $row->wikitext )),	// <content>
					$this->convertTimestamp( $row->timestamp, DATE_ATOM ),	// <updated>
					$titleObj->getFullURL()	// <id>
				);
			}
			$newsItemsCount++;
		}
		$dbr->freeResult( $res );
		wfProfileOut( 'NewsChannel::showChannel' );

		$this->outputChannelMarkup( $newsItems );
	}

	/**
	 * Function finally outputs channel content.
	 *
	 * @param string $newsItems Preformatted news items.
	 */
	function outputChannelMarkup( $newsItems ) {
		global $wgRequest, $wgOut;

		$newsChannelXmlMarkup = array(
		'rss20_body' => '<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title>%s</title>
		<link>%s</link>
		<description>%s</description>
		<language>%s</language>
		<copyright>%s</copyright>
		<image>
			<url>%s</url>
			<title>%s</title>
			<link>%s</link>
		</image>
		<lastBuildDate>%s</lastBuildDate>
		<generator>News Channel 1.52 (MediaWiki extension)</generator>
		<docs>http://www.rssboard.org/rss-specification</docs>
		<ttl>%s</ttl>
		<managingEditor>%s (%s)</managingEditor>
		<webMaster>%s (%s)</webMaster>
		<atom:link href="%s" rel="self" type="application/rss+xml" />%s
	</channel>
</rss>',
		'atom10_body' => '<?xml version="1.0" encoding="utf-8" ?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="%s">
	<title>%s</title>
	<subtitle>%s</subtitle>
	<link href="%s" />
	<id>%s</id>
	<rights>%s</rights>
	<icon>%s</icon>
	<updated>%s</updated>
	<generator>News Channel 1.52 (MediaWiki extension)</generator>
	<author>
		<name>%s</name>
		<email>%s</email>
	</author>
	<link href="%s" rel="self" type="application/atom+xml" />%s
</feed>' );

		$titleObj = Title::makeTitle( NS_SPECIAL, 'NewsChannel' );
		$format = $wgRequest->getVal( 'format' );
		if( $format == 'rss20' ) {
			header( 'Content-type: application/rss+xml; charset=utf-8' );
			$output = sprintf( $newsChannelXmlMarkup["rss20_body"],
				$this->channelTitle, //	<title>
				$this->channelSiteLink, //	<link>
				$this->channelDescription, //	<description>
				$this->channelLanguage, //	<language>
				$this->channelCopyright, //	<copyright>
					$this->channelLogoImage, //	<image><url>
					$this->channelTitle, //	<image><title>
					$this->channelSiteLink, //	<image><link>
				date( DATE_RSS ), //	<lastBuildDate>
				$this->channelUpdateInterval, //	<ttl>
				$this->channelEditorAddress, //	<managingEditor>
				$this->channelEditorName, //	<managingEditor>
				$this->channelWebMasterAddress, //	<webMaster>
				$this->channelWebMasterName, //	<webMaster>
				htmlspecialchars( $wgRequest->getFullRequestURL(), ENT_QUOTES ), //	<atom:link>
				$newsItems //	news items
			);
		}
		elseif( $format == 'atom10' ) {
			header( 'Content-type: application/atom+xml; charset=utf-8' );
			$output = sprintf( $newsChannelXmlMarkup["atom10_body"],
				$this->shortChannelLanguage, //	xml:lang
				$this->channelTitle, //	<title>
				$this->channelDescription, //	<subtitle>
				$this->channelSiteLink, //	<link href="%s" />
				$this->channelSiteLink, //	<id>
				$this->channelCopyright, //	<rights>
				$this->channelLogoImage, //	<icon>
				date( DATE_ATOM ), //	<updated>
				$this->channelEditorName, //	<name>
				$this->channelEditorAddress, //	<email>
				htmlspecialchars( $wgRequest->getFullRequestURL(), ENT_QUOTES ), //	<atom:link>
				$newsItems //	news items
			);
		}
		else {
			$wgOut->showErrorPage( "Error: Unknown output format", "The News Channel extension does not " .
				"support specified output format: {$format}. Please, choose another format." );
			wfProfileOut( 'NewsChannel::showChannel' );
			return;
		}

		$wgOut->clearHTML();
		$wgOut->disable();
		print $output;
	}

	/**
	 * Function converts MySQL database timestamp to specified date format.
	 *
	 * @param string $timestamp Timestamp in MySQL format.
	 * @param string $feedDateFormat Date format as described in http://php.net/manual/en/function.date.php.
	 */
	function convertTimestamp( $timestamp, $feedDateFormat ) {
		$timestamp = preg_replace( '/\D/', '', $timestamp );
		$year 	= substr( $timestamp, 0, 4 );
		$month 	= substr( $timestamp, 4, 2 );
		$day 	= substr( $timestamp, 6, 2 );
		$hour 	= substr( $timestamp, 8, 2 );
		$min 	= substr( $timestamp, 10, 2 );
		$sec 	= substr( $timestamp, 12, 2 );
		return date( $feedDateFormat, gmmktime( $hour, $min, $sec, $month, $day, $year ) );
	}

	/**
	 * Function removes most common wiki markup from news texts.
	 * Some old feed aggregators don't support HTML in news at all.
	 *
	 * @param string $text Text of news item with wiki markup.
	 */
	function purifyTextForFeed( $text ) {
		$text = preg_replace( "/<!--.*?-->/s", '', $text );
		$text = preg_replace( "/\'\'\'(.+?)\'\'\'/", '$1', $text );
		$text = preg_replace( "/\'\'(.+?)\'\'/", '$1', $text );
		$text = preg_replace( "/\{\{.+?}}/s", '', $text );
		$text = preg_replace( "/\[\[({$this->namespacesToRemove}):.+?]]/i", '', $text );
		$text = preg_replace( "/\[\[([^[|\n\]]+)\|([^[\n\]]+)]]/", '$2', $text );
		$text = preg_replace( "/\[\[(.+?)]]/", '$1', $text );
		$text = preg_replace( "/\[([^\s]+) ([^\]\n]+)]/", '$2', $text );
		$text = trim( $text );
		$text = htmlspecialchars( $text, ENT_QUOTES );
		return $text;
	}

	/**
	 * Function "manually" converts basic wiki markup in news texts to HTML markup
	 * to make it readable in feed format. It's faster than using standard MediaWiki
	 * parser in convertMarkupForFeedEx function, but the convertion quality is worse.
	 *
	 * @param string $text Text of news item with wiki markup.
	 */
	function convertMarkupForFeed( $text ) {
		global $wgServer, $wgArticlePath;

		$text = preg_replace( "/<!--.*?-->/s", '', $text );
		$text = preg_replace( "/\'\'\'(.+?)\'\'\'/", '<span style="font-weight:bold">$1</span>', $text );
		$text = preg_replace( "/\'\'(.+?)\'\'/", '<span style="font-style:italic">$1</span>', $text );
		$text = preg_replace( "/\{\{.+?}}/s", '', $text );
		$text = preg_replace( "/\[\[({$this->namespacesToRemove}):.+?]]/i", '', $text );
		$text = preg_replace( "/\[\[([^[|\n\]]+)\|([^[\n\]]+)]]/",
			"<a href=\"{$wgServer}{$wgArticlePath}\">$2</a>", $text );
		$text = preg_replace( "/\[\[(.+?)]]/", "<a href=\"{$wgServer}{$wgArticlePath}\">$1</a>", $text );
		$text = preg_replace( "/\[([^\s]+) ([^\]\n]+)]/", "<a href=\"$1\">$2</a>", $text );
		$text = preg_replace( "/\n======\s*(.+)\s*======\n/", "\n<h6>$1</h6>\n", $text );
		$text = preg_replace( "/\n=====\s*(.+)\s*=====\n/", "\n<h5>$1</h5>\n", $text );
		$text = preg_replace( "/\n====\s*(.+)\s*====\n/", "\n<h4>$1</h4>\n", $text );
		$text = preg_replace( "/\n===\s*(.+)\s*===\n/", "\n<h3>$1</h3>\n", $text );
		$text = preg_replace( "/^==\s*(.+)\s*==\n/", "\n<h2>$1</h2>\n", $text );
		$text = preg_replace( "/\n=\s*(.+)\s*=\n/", "\n<h1>$1</h1>\n", $text );
		$text = trim( $text );
		$text = htmlspecialchars( $text, ENT_QUOTES );
		return $text;
	}

	/**
	 * Function converts wiki markup in news texts to HTML to make it readable in feed format.
	 * Function uses standard MediaWiki parser for convertion.
	 *
	 * @param string $text Text of news item with wiki markup.
	 */
	function convertMarkupForFeedEx( $text ) {
		global $wgServer;

		$this->renderingPage->clearHTML();
		$this->renderingPage->addWikiText( $text );
		$text = $this->renderingPage->getHTML();
		$text = str_replace( " href=\"/", " href=\"{$wgServer}/", $text );
		$text = str_replace( " href='/", " href='{$wgServer}/", $text );
		$text = str_replace( " src=\"/", " src=\"{$wgServer}/", $text );
		$text = str_replace( " src='/", " src='{$wgServer}/", $text );
		$text = htmlspecialchars( $text, ENT_QUOTES );
		return $text;
	}

	/**
	 * Function arranges HTML form in which user can choose feed parameters.
	 */
	function showForm() {
		global $wgContLang, $wgScript, $wgOut;

		$titleObj = Title::makeTitle( NS_SPECIAL, 'NewsChannel' );
		$wgOut->setPagetitle( wfMsg( 'newschannel' ) );
		$msgFormat = wfMsgHtml( 'newschannel_format' );
		$msgLimit = wfMsgHtml( 'newschannel_limit' );
		$msgCat = $wgContLang->getNsText( NS_CATEGORY );
		$msgInCat = wfMsgHtml( 'newschannel_include_category' );
		$msgExCat = wfMsgHtml( 'newschannel_exclude_category' );
		$msgSubmitButton = wfMsgHtml( 'newschannel_submit_button' );

		$htmlDefaultExcludeCategory = '';
		if ( $this->newsWikiExcludeCategory != '' || $this->newsWikiExcludeCategory != null ) {
			$htmlDefaultExcludeCategory =
		"<tr style='margin-top: 2em'>
			<td align='right'>{$msgExCat}</td>
			<td><input type='text' size='60' name='excat' disabled='disabled' value='{$this->newsWikiExcludeCategory}' /></td>
		</tr>";
		}

		$wgOut->addWikiText( $msgComment );
		$wgOut->addHTML( "<form id='newschannel' method='GET' action='{$wgScript}'>
	<input type='hidden' readonly='readonly' name='title' value='{$titleObj->getPrefixedText()}' />
	<table border='0'>
		<tr>
			<td rowspan='1' align='right'>{$msgFormat}</td>
			<td><input tabindex='2' type='radio' checked='checked' name='format' value='rss20' style='border: none; margin-right: 1em' />RSS 2.0</td>
		</tr>
		<tr>
			<td></td>
			<td><input tabindex='3' type='radio' name='format' value='atom10' style='border: none; margin-right: 1em' />Atom 1.0</td>
		</tr>
		<tr style='margin-top: 2em'>
			<td align='right'>{$msgLimit}</td>
			<td><input tabindex='4' type='text' maxlength='5' size='12' name='limit' value='{$this->channelNewsItems}' /></td>
		</tr>
		<tr style='margin-top: 2em'>
			<td align='right'>{$msgCat}</td>
			<td><input type='text' size='60' name='cat' disabled='disabled' value='{$this->newsWikiCategory}' /></td>
		</tr>
		<tr style='margin-top: 2em'>
			<td align='right'>{$msgInCat}</td>
			<td><input type='text' size='60' name='cat1' value='' /></td>
		</tr>
		<tr style='margin-top: 2em'>
			<td align='right'>{$msgInCat}</td>
			<td><input type='text' size='60' name='cat2' value='' /></td>
		</tr>
		{$htmlDefaultExcludeCategory}
		<tr style='margin-top: 2em'>
			<td align='right'>{$msgExCat}</td>
			<td><input type='text' size='60' name='excat1' value='' /></td>
		</tr>
		<tr style='margin-top: 2em'>
			<td align='right'></td>
			<td style='padding-top: 1em' align='left'>
				<input tabindex='5' type='submit' name='wpSubmitNewsChannelParams' value='{$msgSubmitButton}' />
			</td>
		</tr>
	</table>
	</form>\n" );
	}
}
?>
