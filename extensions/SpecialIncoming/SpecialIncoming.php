<?php
/*
 * An extension to allow only logged-in users to browse a given directory
 */

$wgExtensionFunctions[] = "wfSpecialIncomingExtension";

// function adds the wiki extension
function wfSpecialIncomingExtension() {
	global $wgHooks, $wgSpecialPages;
	$wgSpecialPages['Incoming'] = 'SpecialIncoming';
	$wgHooks['MessagesPreLoad'][] = 'wfSpecialIncomingMessagesPreLoad';
}

$wgSpecialIncomingMessages = array(
	'Right-incoming' => "Can access files uploaded with Fiji/ImageJ2's <i>Help>Upload Sample Image</i> via Special:Incoming"
);

function wfSpecialIncomingMessagesPreLoad( $title, &$text ) {
     global $wgSpecialIncomingMessages;
     if ( isset( $wgSpecialIncomingMessages[$title] ) ) {
          $text = $wgSpecialIncomingMessages[$title];
     }
     return true;
}

class SpecialIncoming extends SpecialPage {
	var $incoming;
	var $promotion, $promotionURL;
	var $dirIcon;
	var $ascending;
	var $sortkey;
	var $geomap;

	function SpecialIncoming()
	{
		global $wgIncomingDirectory, $wgIncomingPromotionDirectory;
		$incoming = isset($wgIncomingDirectory) ? $wgIncomingDirectory : '/tmp/';
		$this->promotion = isset($wgIncomingPromotionDirectory) ? $wgIncomingPromotionDirectory : null;
		if ($this->promotion == null || !$this->startsWith($this->promotion, $_SERVER['DOCUMENT_ROOT'])) {
			$this->promotionURL = null;
		} else {
			$url = $_SERVER['SCRIPT_URI'];
			$slash = strrpos($url, '/', 10);
			$this->promotionURL = $slash < 0 ? $url : substr($url, 0, $slash);
			$this->promotionURL .= substr($this->promotion, strlen($_SERVER['DOCUMENT_ROOT']));
			if (!$this->endsWith($this->promotionURL, '/')) {
				$this->promotionURL .= '/';
			}
		}

		SpecialPage::SpecialPage('Incoming', 'incoming');

		if ($incoming == '')
			$incoming = '/invalid/';
		else if (!$this->endsWith($incoming, '/'))
			$incoming .= '/';
		$this->incoming = $incoming;

		$path = preg_replace('/[^\/]*$/', '', __FILE__);

		global $wgScriptPath;
		$extensionDirectory = $wgScriptPath . '/extensions/SpecialIncoming/';
		$this->dirIcon = '<img src="' . $extensionDirectory
				. 'Gnome-document-open.svg" width="16"'
				. ' alt="DIR"/>';
	}

	function getDescription() {
		return "Special:Incoming";
	}

	// Generate the HTML for a given month
	function getHTML()
	{
		global $wgUser;
		if ($wgUser->isAllowed( 'incoming' ) ) {
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
			return 'This page is restricted to users with the <i>incoming</i> right only.';
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

	function getGeoMap() {
		if (isset($this->geomap))
			return;
		$this->geomap = array();
		$handle = fopen('/var/www/uploads/map.txt', 'r');
		if (!$handle)
			return;
		$mysql = mysql_connect('localhost', 'root', '');
		if (!$mysql) {
			fclose($handle);
			return;
		}
		mysql_select_db('ipinfodb', $mysql);
		while (($line = fgets($handle)))
			if (preg_match('/^([^ ]*) (.*)\n?$/', $line, $matches)) {
				$numbers = preg_split('/\./', $matches[1]);
				$ip = $numbers[0] * 0x100000 + $numbers[1] * 0x10000 + $numbers[2] * 0x100 + $numbers[3];
				$result = mysql_query('SELECT latitude, longitude FROM ip_group_city WHERE ip_start <= ' . $ip . ' ORDER BY ip_start DESC LIMIT 1', $mysql);
				$line = mysql_fetch_array($result);
				$this->geomap[$matches[2]] = array($matches[1], $line ? $line[0] . ',' . $line[1] : '');
			}
		fclose($handle);
	}

	function listing() {
		if (isset($_GET['geolink'])) {
			if (!preg_match('/^[-+]?[\.0-9]+\s*,\s*[-+]?[\.0-9]+$/', $_GET['geolink']))
				return 'Invalid coordinates: ' . htmlspecialchars($_GET['geolink']);
			return '<div id="map_canvas" style="width:100%; height:80%"></div>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false&key=ABQIAAAAR6MdZR1iDc4XOW6L4yy9AhT4kiVGNpNBiRrgOV1HD4AqqeLlaBS23KFDtfTyZ8bYP8zlH6tRHPyj0Q"></script>
<script>
    var latlng = new google.maps.LatLng(51.058671, 13.784402);
    var myOptions = {
      zoom: 1,
      center: latlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    new google.maps.Marker({ position : new google.maps.LatLng(' . $_GET['geolink'] . '), map: map });
</script>
';
		}
		#$this->getGeoMap();

		$dir = isset($_GET['dir']) ? $_GET['dir'] : $this->incoming;

		$is_dir = is_dir($dir);
		if ($is_dir && !$this->endsWith($dir, '/'))
			$dir .= '/';
		if (!$this->startsWith($dir, $this->incoming)
				|| strstr($dir, '/../') !== false
				|| (!is_dir($dir) && !is_file($dir)))
			return 'Do not try to be sneaky.';

		if (is_file($dir)) {
			/*
				This did not work with our old server's PHP:
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$content_type = finfo_file($finfo, $dir);
			finfo_close($finfo);
			*/
			$content_type = mime_content_type($dir);
			if ($this->endsWith($dir, '.flv'))
				$content_type = 'video/x-flv';
			header('Content-Type: ' . $content_type);
			header('Content-Length: ' . (string)filesize($dir), true);
			header('Content-Disposition: inline; filename="'
				. substr($dir, strrpos($dir, '/') + 1) . '"');
			session_write_close();
			if ($fd = fopen($dir, 'rb')) {
				while(!feof($fd)) {
					print fread($fd, 4096);
					flush();
					ob_flush();
				}
				fclose($fd);
			}
			exit;
		}

		$html = '';
		if ($this->promotion != null && isset($_GET['promote'])) {
			$filename = $_GET['promote'];
			$source = $dir . '/' . $filename;
			$destination = $this->promotion . '/' . $filename;
			if (!$this->startsWith($_GET['promote'], '.') &&
					file_exists($source) &&
					!file_exists($destination)) {
				rename($source, $destination);
			}
			if ($this->promotionURL != null) {
				$url = $this->promotionURL . $filename;
				$html .= '<h1>Promotion successful!</h1>'
					. 'The file ' . $filename
					. ' was moved to <a href="'
					. $url . '">' . $url . '</a>.';
			}
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
			$geolink = '';
			if (isset($this->geomap[$key])) {
				if ($this->geomap[$key][1] != '')
					//$geolink = $skin->link($wgTitle, ' (' . $this->geomap[$key][0] . ')', array(), array('geolink' => $this->geomap[$key][1]), array('known', 'noclasses'));
					$geolink = ' (<a href="http://maps.google.com/maps?q=' . str_replace(array('+', ' '), array('%2B', '+'), $this->geomap[$key][1]) . '+(' . urlencode($this->geomap[$key][0]) . ')">' . htmlspecialchars($this->geomap[$key][0]) . '</a>)';
				else
					$geolink = ' (' . $this->geomap[$key] . ')';
			}
			$promote = '';
			if (!$is_dir && $this->promotion != null) {
				$destination = $this->promotion . '/' . $key;
				if (!file_exists($destination))
					$promote = "\t\t<td>" . $skin->link(
						$wgTitle,
						'Promote',
						array(),
						array('dir' => $dir,
							'promote' => $key),
						array('known', 'noclasses'));
					if ($this->promotionURL != null) {
							$url = $this->promotionURL . $key;
							$promote .= ' to ' . $url;
					}
					$promote .= "</td>\n";
			}
			$list .= "\t<tr>\n"
				. "\t\t<td>" . ($is_dir ? $this->dirIcon
					: '&nbsp;') . "</td>\n"
				. "\t\t<td>" . $skin->link(
					$wgTitle,
					$key,
					array(),
					array('dir' => $stat['path']),
					array('known', 'noclasses'))
					. $geolink
					. "</td>\n"
				. "\t\t<td>" . ($is_dir ? '&nbsp;'
					: $stat['size']) . "</td>\n"
				. "\t\t<td>" . gmstrftime("%d.%m.%Y&nbsp;%H:%M:%S",
					$stat['mtime']) . "</td>\n"
				. $promote
				. "\t</tr>\n";
		}
		$list .= "</table>\n";

		return $html
			. '<h1>' . $skin->link(
				$wgTitle, 'Incoming/', array(), array(),
				array('known', 'noclasses'))
			. substr($dir, strlen($this->incoming)) . "</h1>\n"
			. $list;
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
