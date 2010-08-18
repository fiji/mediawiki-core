<?php
/*
 * An extension to allow only logged-in users to browse a given directory
 */

$wgExtensionFunctions[] = "wfSpecialIncomingExtension";

// function adds the wiki extension
function wfSpecialIncomingExtension() {
	global $wgHooks;
	SpecialPage::addPage(new SpecialIncoming());
}

require_once("$IP/includes/SpecialPage.php");

class SpecialIncoming extends SpecialPage {
	var $incoming;
	var $dirIcon;
	var $ascending;
	var $sortkey;

	function SpecialIncoming($incoming = "/var/www/uploads/incoming/")
	{
		SpecialPage::SpecialPage('Incoming');

		if ($incoming == '')
			$incoming = '/invalid/';
		else if (!$this->endsWith($incoming, '/'))
			$incoming .= '/';
		$this->incoming = $incoming;

		$path = ereg_replace("[^/]*$", "", __FILE__);
		if ($this->startsWith($path, $_SERVER['DOCUMENT_ROOT']))
			$this->dirIcon = '<img src="/' . substr($path,
				strlen($_SERVER['DOCUMENT_ROOT']))
				. '/Gnome-document-open.svg" width="16"'
				. ' alt="DIR"/>';
		else
			$this->dirIcon = 'DIR';
	}

	function getDescription() {
		return "Special:Incoming";
	}

	// Generate the HTML for a given month
	function getHTML()
	{
		global $wgUser;
		if ($wgUser->isLoggedIn()) {
			return $this->listing();
		}
		else {
			global $wgTitle;
			$skin = $wgUser->getSkin();
			$loginTitle = SpecialPage::getTitleFor( 'Userlogin' );
			$loginLink = $skin->link(
					$loginTitle,
					'log in',
					array(),
					array( 'returnto' => $wgTitle->getPrefixedText() ),
					array( 'known', 'noclasses' )
					);
			return 'This page is restricted to registered users only. You need to ' . $loginLink;
		}
	}

	function endsWith($haystack, $needle) {
		$haylen = strlen($haystack);
		$needlen = strlen($needle);
		if ($haylen < $needlen)
			return false;
		return !strcmp(substr($haystack, $haylen - $needlen), $needle);
	}

	function startsWith($haystack, $needle) {
		$haylen = strlen($haystack);
		$needlen = strlen($needle);
		if ($haylen < $needlen)
			return false;
		return !strcmp(substr($haystack, 0, $needlen), $needle);
	}

	function listing() {
		$dir = isset($_GET['dir']) ? $_GET['dir'] : $this->incoming;

		$is_dir = is_dir($dir);
		if ($is_dir && !$this->endsWith($dir, '/'))
			$dir .= '/';

		if (!$this->startsWith($dir, $this->incoming)
				|| strstr($dir, '/../') !== false
				|| (!is_dir($dir) && !is_file($dir)))
			return 'Do not try to be sneaky.';

		if (is_file($dir)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			header('Content-Type: ' . finfo_file($finfo, $dir));
			finfo_close($finfo);
			$stat = lstat($dir);
			header('Content-Length: ' . $stat['size']);
			header('Content-Disposition: inline; filename="'
				. substr($dir, strrpos($dir, '/') + 1) . '"');
			fpassthru(fopen($dir, 'rb'));
			exit;
		}

		$this->sortkey = isset($_GET['order']) ? $_GET['order'] : 'mtime';
		$this->ascending = isset($_GET['ascending']) ? $_GET['ascending'] : false;

		$items = array();
		$handle = opendir($dir);
		while (($file = readdir($handle)) !== false) {
			if ($file == '.')
				continue;
			if ($file == '..') {
				$offset = strrpos($dir, '/', -2);
				$path = substr($dir, 0, $offset + 1);
				if (!$this->startsWith($path, $this->incoming))
					continue;
			} else
				$path = $dir . $file;

			if (!is_dir($path) && !is_file($path))
				continue;
			$items[$file] = stat($path);
			$items[$file]['path'] = $path;
		}
		closedir($handle);

		if ($this->sortkey == 'filename') {
			if ($this->ascending)
				ksort($items);
			else
				krsort($items);
		}
		else
			uasort($items, array($this, 'cmp'));

		global $wgUser, $wgTitle;
		$skin = $wgUser->getSkin();
		$list = "<table>\n"
			. "\t<tr>\n"
			. "\t\t<th>Type</th>\n";
		foreach (array("Filename", "Size", "Date") as $key) {
			$order = $key == 'Date' ? 'mtime' : strtolower($key);
			$ascending = $order != 'mtime';
			if ($this->sortkey == $order)
				$ascending = !$this->ascending;
				
			$list .= "\t\t<th>" . $skin->link(
				$wgTitle, $key, array(),
				array('dir' => $dir,
					'order' => $order,
					'ascending' => $ascending),
				array('known', 'noclasses')) . "</th>\n";
		}
		$list .= "\t</tr>\n";
		foreach ($items as $key => $stat) {
			$stat = $items[$key];
			$is_dir = is_dir($stat['path']);
			$list .= "\t<tr>\n"
				. "\t\t<td>" . ($is_dir ? $this->dirIcon
					: '&nbsp;') . "</td>\n"
				. "\t\t<td>" . $skin->link(
					$wgTitle,
					$key,
					array(),
					array('dir' => $stat['path']),
					array('known', 'noclasses'))
					. "</td>\n"
				. "\t\t<td>" . ($is_dir ? '&nbsp;'
					: $stat['size']) . "</td>\n"
				. "\t\t<td>" . gmstrftime("%d.%m.%Y&nbsp;%H:%M:%S",
					$stat['mtime']) . "</td>\n"
				. "\t</tr>\n";
		}
		$list .= "</table>\n";

		return '<h1>Incoming/' . substr($dir, strlen($this->incoming))
			. "</h1>\n" . $list;
	}

	function cmp($a, $b) {
		if ($a[$this->sortkey] == $b[$this->sortkey])
			return 0;
		return ($this->ascending ? 1 : -1)
			* ($a[$this->sortkey] - $b[$this->sortkey]);
	}

	function execute($par) {
		global $wgOut;

		$this->setHeaders();

		$wgOut->addHTML($this->getHTML());
	}
}
