<?php
/*
 * An extension to allow accounts in the "uploaders" group change their WebDAV password.
 */

$wgExtensionFunctions[] = "wfSpecialChangeUploadPasswordExtension";

// function adds the wiki extension
function wfSpecialChangeUploadPasswordExtension() {
	global $wgHooks;
	SpecialPage::addPage(new SpecialChangeUploadPassword());
}

require_once("$IP/includes/SpecialPage.php");

class SpecialChangeUploadPassword extends SpecialPage {

	function SpecialChangeUploadPassword()
	{
		SpecialPage::SpecialPage('ChangeUploadPassword', 'change-upload-password');
	}

	function getDescription() {
		return "Special:ChangeUploadPassword";
	}

	// Generate the HTML for a given month
	function getHTML()
	{
		global $wgUser;
		if ($wgUser->isAllowed( 'change-upload-password' ) ) {
			return $this->showForm();
		} else {
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
			return 'This page is restricted to users with the <i>change-upload-password</i> right only.';
		}
	}

	function showForm() {
		global $wgUser, $wgTitle, $wgChangeUploadPasswordFile;
		$skin = $wgUser->getSkin();
		if (!isset($wgChangeUploadPasswordFile) ||
				!file_exists($wgChangeUploadPasswordFile)) {
			return "Extension not yet configured!";
		}
		if (isset($_POST['password'])) {
			exec("htpasswd -b " . escapeshellarg($wgChangeUploadPasswordFile) . " " . escapeshellarg($wgUser->getName()) . " " . escapeshellarg($_POST['password']), $output, $return);
			$html = "";
			foreach ($output as $line) {
				$html .= htmlentities($line) . "<br />\n";
			}
			if ($return !== 0) {
				$html .= '<span style="color:red">Failed!</span>';
			} else {
				$html .= 'Password changed.';
			}
			return $html;
		}
		return '<h1>Change upload password for ' . $wgUser->getName()
			. "</h1>\n"
			. '<form method="POST">'
			. '<label for="password">Password</label>'
			. '<input type="password" id="password" name="password" />'
			. '</form>';
	}

	function execute($par) {
		global $wgOut;

		$this->setHeaders();

		$wgOut->addHTML($this->getHTML());
	}

	function salt() {
		return substr(str_shuffle('./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, 8);
	}

	function htpasswd($salt, $passwd) {
		$len = strlen($passwd);
		$text = $passwd . '$apr1$' . $salt;
		$bin = pack('H32', md5($passwd . $salt . $passwd));
		for ($i = $len; $i > 0; $i -= 16) {
			 $text .= substr($bin, 0, min(16, $i));
		}
		for ($i = $len; $i > 0; $i >>= 1) {
			$text .= ($i & 1) ? chr(0) : $passwd{0};
		}
		$bin = pack('H32', md5($text));
		for ($i = 0; $i < 1000; $i++) {
			$new = ($i & 1) ? $passwd : $bin;
			if ($i % 3) $new .= $salt;
			if ($i % 7) $new .= $passwd;
			$new .= ($i & 1) ? $bin : $passwd;
			$bin = pack('H32', md5($new));
		}
		for ($i = 0; $i < 5; $i++) {
			$k = $i + 6;
			$j = $i + 12;
			if ($j == 16) {
				$j = 5;
			}
			$tmp = $bin[$i] . $bin[$k] . $bin[$j] . $tmp;
		}
		$tmp = chr(0) . chr(0) . $bin[11] . $tmp;
		$tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
			'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',
			'./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
		return '$' . 'apr1' . '$' . $salt . '$' . $tmp;
	}
}
