<?
apache_setenv( 'MW_INSTALL_PATH', '/var/www/vhosts/fiji.imagej.net/mediawiki/phase3/' );
$_SERVER['SCRIPT_FILENAME'] = preg_replace( '/^\/var\/www\/vhosts\/fiji.imagej.net\/mediawiki\/phase3\/wiki\//', '/var/www/vhosts/fiji.imagej.net/mediawiki/phase3/', $_SERVER['SCRIPT_FILENAME'] );
$_SERVER['REQUEST_URI'] = preg_replace( '/^\/wiki\//', '/', $_SERVER['REQUEST_URI'] );
$_SERVER['SCRIPT_NAME'] = preg_replace( '/^\/wiki\//', '/', $_SERVER['SCRIPT_NAME'] );
$_SERVER['SCRIPT_URL'] = preg_replace( '/^\/wiki\//', '/', $_SERVER['SCRIPT_URL'] );
if (!isset($_GET['title'])) {
        $_GET['title'] = 'Fiji';
}
require_once('/var/www/vhosts/fiji.imagej.net/mediawiki/phase3/index.php');
//echo $_SERVER['SCRIPT_NAME'];
?>
