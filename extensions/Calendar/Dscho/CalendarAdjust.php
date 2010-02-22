<?php
// This file will modify the month and year of the calendar in the session object.
// it will also handle creating a new event

// determine the local base path to this wiki (rather than using server variable DOCUMENT_ROOT)
$wgLocalPath = str_replace("\\", "/" , substr($_SERVER["SCRIPT_FILENAME"], 0, strlen($_SERVER["SCRIPT_FILENAME"]) - strlen($_SERVER["SCRIPT_NAME"])));

// check for the parameters
if (isset($_GET["month"]) && isset($_GET["year"]) && isset($_GET["title"]) && isset($_GET["name"]) && !isset($_GET["day"]) && isset($_GET['referer'])) {
	// generate the cookie name
	$cookie_name = 'calendar_' . str_replace(' ', '_', $_GET["title"]) . str_replace(' ', '_', $_GET["name"]);
	
	//save the calendar back into the session
	setcookie($cookie_name, $_GET["month"] . "`" . $_GET["year"] . "`" . $_GET["title"] . "`" . $_GET["name"], 0, '/', '');

	// write out refresh, so cookie gets set, but this page won't appear in the browser's cache
	echo "<html><head><META HTTP-EQUIV=\"refresh\" CONTENT=\"0;URL=" . $_GET['referer'] . "\">";	
}
else if (isset($_GET["month"]) && isset($_GET["year"]) && isset($_GET["day"]) && isset($_GET["title"]) && isset($_GET["name"]) && isset($_GET["path"])) {
	// ========== Include what is need to do article lookups ==========
	# needed by MediaWiki code
	define( 'MEDIAWIKI', true );

	# Load up some global defines.
	require_once( $wgLocalPath . $_GET["path"] . '/includes/Defines.php' );	
	# Include this site setttings
	require_once( $wgLocalPath . $_GET["path"] . '/LocalSettings.php' );
	#include profiler stub (ignore error if not found)
	if (file_exists($wgLocalPath . $_GET["path"] . '/includes/ProfilerStub.php')) {
		require_once( $wgLocalPath . $_GET["path"] . '/includes/ProfilerStub.php');
	}
	# Prepare MediaWiki
	require_once( $wgLocalPath . $_GET["path"] . '/includes/Setup.php' );
	
	// ========== Do article lookups ==========
	// the name of the article to check for
	$articleName = "";
	// the article count
	$articleCount = 0;
	
	// keep searching until name not found
	// generate name
	$articleName = $_GET["title"] . "/Calendar_";
	if ($_GET["name"] != "") {
		$articleName .= "\"" . $_GET["name"] . "\"_";
	}
	$articleName .= "(" . $_GET["month"] . "-" . $_GET["day"] . "-" . $_GET["year"] . ")_-_Event_" . ($articleCount + 1);
	$article = new Article(Title::newFromText($articleName));
	while ($article->exists() && $articleCount < 20) {		
		// increment count
		$articleCount += 1;
		
		// generate name
    	$articleName = $_GET["title"] . "/Calendar_";
		if ($_GET["name"] != "") {
			$articleName .= "\"" . $_GET["name"] . "\"_";
		}
		$articleName .= "(" . $_GET["month"] . "-" . $_GET["day"] . "-" . $_GET["year"] . ")_-_Event_" . ($articleCount + 1);
    	$article = new Article(Title::newFromText($articleName));
	}
	
	// redirect the user to the new article
	header("Location: " . $_GET["path"] . "/index.php?title=" . urlencode($articleName) . "&action=edit");
	print "here";
}
else {
	print "An error has occurred with the Calendar extension.";
}
?>