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
}
