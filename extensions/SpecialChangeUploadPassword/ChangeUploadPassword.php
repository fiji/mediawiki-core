<?php
/*
 * An extension to allow accounts in the "uploaders" group change their WebDAV password.
 */

$wgExtensionFunctions[] = "wfSpecialChangeUploadPasswordExtension";

// function adds the wiki extension
function wfSpecialChangeUploadPasswordExtension() {
	global $wgHooks, $wgSpecialPages;
	$wgSpecialPages['ChangeUploadPassword'] = 'SpecialChangeUploadPassword';
	$wgHooks['MessagesPreLoad'][] = 'wfSpecialChangeUploadPasswordMessagesPreLoad';
}

$wgSpecialChangeUploadPasswordMessages = array(
	'Right-change-upload-password' => "Can add/change a password for uploading to the Fiji Update Site"
);

function wfSpecialChangeUploadPasswordMessagesPreLoad( $title, &$text ) {
     global $wgSpecialChangeUploadPasswordMessages;
     if ( isset( $wgSpecialChangeUploadPasswordMessages[$title] ) ) {
          $text = $wgSpecialChangeUploadPasswordMessages[$title];
     }
     return true;
}

function wfChangePersonalUploadPassword($userName, $password, &$output, &$return) {
	$conf = $_SERVER['DOCUMENT_ROOT'] . '/../../conf/';
	exec("ssh -F " . escapeshellarg($conf . 'ssh_config')
		. " -o StrictHostKeyChecking=yes"
		. " -o UserKnownHostsFile=" . escapeshellarg($conf . 'known_hosts')
		. " sites "
		. escapeshellarg($userName . ' ' . $password) . ' 2>&1',
		$output, $return);
}

class SpecialChangeUploadPassword extends SpecialPage {

	function __construct()
	{
		parent::__construct('ChangeUploadPassword');
	}

	function getDescription() {
		return "Initialize or Change Upload Password";
	}

	// Generate the HTML for a given month
	function getHTML()
	{
		global $wgUser;
		if ($wgUser->isEmailConfirmed()) {
			return $this->showForm();
		} else {
			global $wgTitle;
			$skin = $wgUser->getSkin();
			$loginTitle = SpecialPage::getTitleFor( 'Userlogin', 'emailconfirmed' );
			$loginLink = $skin->link(
					$loginTitle,
					'log in',
					array(),
					array( 'returnto' => $wgTitle->getPrefixedText() ),
					array( 'known', 'noclasses' )
					);
			return 'This page is restricted to <i>authenticated</i> users only.';
		}
	}

	function showForm() {
		global $wgUser, $wgTitle, $wgChangeUploadPasswordFile;
		if (!$wgUser->isEmailConfirmed()) {
			return "Internal error 517.";
		}
		if (!isset($wgChangeUploadPasswordFile) ||
				!file_exists($wgChangeUploadPasswordFile)) {
			return "Extension not yet configured!";
		}
		if (isset($_POST['password'])) {
			if (!isset($_POST['password2']) || $_POST['password'] !== $_POST['password2']) {
				return 'Passwords do not match!';
			}
			if (!isset($_POST['site'])) return "Nice try.";
			$updateSiteHint = '';
			setlocale(LC_ALL, 'en_US.UTF-8');
			putenv('LC_ALL', 'en_US.UTF-8');
			if ($_POST['site'] == 'fiji.sc') {
				if ($wgUser->isAllowed( 'change-upload-password' ) ) {
					exec("htpasswd -b " . escapeshellarg($wgChangeUploadPasswordFile)
						. " " . escapeshellarg($wgUser->getName()) . " "
						. escapeshellarg($_POST['password']), $output, $return);
					$updateSiteHint = "To upload, change the sshHost of the 'Fiji' update site to\n"
						.  "\twebdav:" . $wgUser->getName() . "\n"
						. "in Advanced Mode's 'Manage Update Sites'.\n";
				} else {
					return "Nice try!";
				}
			} elseif ($_POST['site'] == 'private') {
				wfChangePersonalUploadPassword($wgUser->getName(), $_POST['password'], $output, $return);
				$updateSiteHint = "To upload, add a new update site (check 'for upload' before clicking 'Add')\n"
					. "or change your existing one. You need to set the URL to \n"
					. "\thttp://sites.imagej.net/" . $wgUser->getName() . "/\n"
					. "the sshHost to\n"
					. "\twebdav:" . $wgUser->getName() . "\n"
					. "and set the upload directory to '.' in updater's Advanced Mode's 'Manage Update Sites'.\n";
			} else {
				return 'Internal error 1337.';
			}
			$html = "";
			foreach ($output as $line) {
				$html .= htmlentities($line) . "<br />\n";
			}
			if ($return !== 0) {
				$html .= '<span style="color:red">Failed!</span>';
			} else {
				$html .= '<h2>Password changed.</h2>'
					. str_replace(array("\n", "\t"), array("<br />\n", "&nbsp;&nbsp;&nbsp;&nbsp;"), htmlentities($updateSiteHint)) . "\n";
				$wgUser->sendMail("fiji.sc upload password changed",
					"Your fiji.sc upload password was changed. If you did not intend to do this,\n"
					. "please visit http://fiji.sc/Special:ChangeUploadPassword and change it back.\n"
					. "\n" . $updateSiteHint . "\n"
					. "Have fun uploading,\n"
					. "Yours sincerely, the Fiji Wiki\n");
			}
			return $html;
		}
		$siteChoice = '<input type="hidden" name="site" value="private">';
		if ( $wgUser->isAllowed( 'change-upload-password' ) ) {
			$siteChoice = '<tr><td>Update site</td><td>'
				. '<input type="radio" name="site" value="fiji.sc" checked>Fiji\'s main update site</input><br />'
				. '<input type="radio" name="site" value="private">Your <a href="http://sites.imagej.net/'
				. $wgUser->getName() . '/">personal update site</a></input>'
				. '</td></tr>';
		}
		return '<h1>Initialize or change upload password for ' . $wgUser->getName()
			. "</h1>\n"
			. '<form method="POST" accept-charset="UTF-8">'
			. '<table>' . $siteChoice
			. '<tr>'
			. '<td><label for="password">Password</label></td>'
			. '<td><input type="password" id="password" name="password" /></td>'
			. '</tr><tr>'
			. '<td><label for="password2">Confirm password</label></td>'
			. '<td><input type="password" id="password2" name="password2" /></td>'
			. '</tr><tr>'
			. '<td colspan=2><input type="submit"></td>'
			. '</table>'
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

class ApiChangeUploadPassword extends ApiBase {
	public function execute() {
		$currentUser = $this->getUser();
		if ( $currentUser->isAnon() ||
				!$currentUser->isEmailConfirmed() ) {
			$this->dieUsageMsg( 'badaccess-group0' );
		}
		$params = $this->extractRequestParams();
		wfChangePersonalUploadPassword($currentUser->getName(), $params[ 'password' ], $output, $return);
		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			array( 'result' => $return,
				'output' => join( "\n", $output ) )
		);
	}

	public function isWriteMode() {
		return true;
	}

	public function getAllowedParams() {
		return array(
			'password' => array(
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => 'string'
			)
		);
	}

	public function getParamDescription() {
		return array(
			'password' => "The upload password for the user's personal update site"
		);
	}

	public function getDescription() {
		return array(
			'Change the password for a personal update site'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(),
			array( 'badaccess-group0' )
		);
	}

	public function getExamples() {
		return array(
			'api.php?action=changeuploadpassword&password=HeyaVoth'
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 1.0';
	}

}

/**
 * Create an account on the Fiji Wiki.
 *
 * <p>The Fiji Wiki does not follow the MediaWiki release cycle slavishly;
 * besides, the createaccount API required an alpha-quality MediaWiki version
 * at the time this class was written.</p>
 */
class ApiCreateFijiWikiAccount extends ApiBase {
	public function execute() {
		global $wgAuth, $wgNewPasswordExpiry, $wgRequest, $wgScript, $wgServer;

		# Check permissions
		$currentUser = $this->getUser();
		if ( !$currentUser->isAllowed( 'createaccount' ) ||
				$currentUser->isBlockedFromCreateAccount() ||
				$currentUser->isDnsBlacklisted( $wgRequest->getIP(), true ) ) {
			$this->dieUsageMsg( 'badaccess-group0' );
		}

		$headers = apache_request_headers();
		$params = $this->extractRequestParams();
		if ( $wgAuth->userExists( $params[ 'name' ] ) ||
				!Sanitizer::validateEmail( $params[ 'email' ] ) ||
				!isset( $headers[ 'Requested-User' ] ) ||
				$headers[ 'Requested-User' ] !== $params[ 'name' ] ) {
			$this->dieUsageMsg( 'badaccess-group0' );
		}

		# Now create a dummy user ($u) and check if it is valid
		$name = trim( $params[ 'name' ] );
		$u = User::newFromName( $name, 'creatable' );
		if ( !is_object( $u ) || 0 != $u->idForName() ) {
			$this->dieUsageMsg( 'badaccess-group0' );
		}

		# Set some additional data so the AbortNewAccount hook can be used for
		# more than just username validation
		$u->setEmail( $params[ 'email' ] );
		$u->setRealName( $params[ 'realname' ] );

		if( !$wgAuth->addUser( $u, null, $params[ 'email' ], $params[ 'realname' ] ) ) {
			$this->dieUsageMsg( 'badaccess-group0' );
		}

		$u->addToDatabase();
		$u->setToken();
		$wgAuth->initUser( $u, true );
		$u->setOption( 'rememberpassword', 0 );
		$np = $u->randomPassword();
		$u->setNewpassword( $np, true );
		$userLanguage = $u->getOption( 'language' );
		$m = wfMessage( 'passwordremindertext', $wgRequest->getIP(), $u->getName(), $np, $wgServer . $wgScript,
			round( $wgNewPasswordExpiry / 86400 ) )->inLanguage( $userLanguage )->text();
		$result = $u->sendMail( wfMessage( 'passwordremindertitle' )->inLanguage( $userLanguage )->text(), $m );
		$u->saveSettings();

		# Update user count
		$ssUpdate = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
		$ssUpdate->doUpdate();

		wfRunHooks( 'AddNewAccount', array( $u, true ) );
		$u->addNewUserLogEntry( true, $params[ 'reason' ] );

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			array( 'created' => $params[ 'name' ] )
		);
	}

	public function getAllowedParams() {
		return array(
			'name' => array(
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => 'string'
			),
			'realname' => array(
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => 'string'
			),
			'email' => array(
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => 'string'
			),
			'reason' => array(
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => 'string'
			)
		);
	}

	public function getParamDescription() {
		return array(
			'name' => "The desired name for the account to be created",
			'realname' => "The real name of the person behind the account to be created",
			'email' => "A valid email address to activate the account",
			'reason' => "A description for the log"
		);
	}

	public function getDescription() {
		return array(
			'Create an account on the Fiji Wiki'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(),
			array( 'badaccess-group0' )
		);
	}

	public function getExamples() {
		return array(
			'api.php?action=createimagejwikiaccount&name=honestjohnny&realname=Real+Honest+Johnny&email=johnny@example.com&reason=I+wants+it'
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 1.0';
	}

}

$wgAPIModules['changeuploadpassword'] = 'ApiChangeUploadPassword';
$wgAPIModules['createimagejwikiaccount'] = 'ApiCreateFijiWikiAccount';
$wgAPIModules['createfijiwikiaccount'] = 'ApiCreateFijiWikiAccount';
