<?
$path = $_SERVER['SCRIPT_FILENAME'];
for (;;) {
	$parent = preg_replace('/\/[^\/]*$/', '', $path);
	if ($parent == $path) {
		die("Internal error!");
	}
	if (file_exists($parent . '/LocalSettings.php')) {
		break;
	}
	$path = $parent;
}
apache_setenv('MW_INSTALL_PATH', $parent . '/');
$path = $_SERVER['SCRIPT_FILENAME'];
$_SERVER['SCRIPT_FILENAME'] = $parent . '/index.php';
if ($path == $_SERVER['SCRIPT_FILENAME']) {
	die("Invalid path: $path");
}

$offset = strlen($_SERVER['SCRIPT_FILENAME']) - 10;
$regex = '/^' . preg_replace('/\//', '\/', substr($path, $offset, strlen($path) - 10 - $offset)) . '\//';

$_SERVER['REQUEST_URI'] = preg_replace($regex, '/', $_SERVER['REQUEST_URI']);
$_SERVER['SCRIPT_NAME'] = preg_replace($regex, '/', $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_URL'] = preg_replace($regex, '/', $_SERVER['SCRIPT_URL']);

if (!isset($_GET['title'])) {
        $_GET['title'] = 'Fiji';
}

require_once($_SERVER['SCRIPT_FILENAME']);
?>

