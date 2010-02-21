<?

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionFunctions[] = 'wfSetupExtGetVariables';

$wgExtensionCredits['parserhook'][] = array(
	'name' => 'ExtGetVariables',
	'version' => '0.1',
	'url' => 'http://www.mediawiki.org/wiki/Extension:ExtGetVariables',
	'author' => 'Johannes Schindelin',
	'description' => 'Access _GET variables',
);

$wgHooks['LanguageGetMagic'][] = 'wfGetVariablesLanguageGetMagic';

class ExtGetVariables {
	function registerParser( &$parser ) {
		if ( defined( get_class( $parser ) . '::SFH_OBJECT_ARGS' ) ) {
			$parser->setFunctionHook( 'getvar', array( &$this, 'getvar' ), SFH_OBJECT_ARGS );
			$parser->setFunctionHook( 'ifgetvar', array( &$this, 'ifgetvar' ), SFH_OBJECT_ARGS );
		} else {
			$parser->setFunctionHook( 'getvar', array( &$this, 'getvar' ) );
			$parser->setFunctionHook( 'ifgetvar', array( &$this, 'ifgetvar' ) );
		}
	}

	function getvar( &$parser, $name = '' ) {
		return htmlspecialchars($_GET[$name]);
	}

	function ifgetvar( &$parser, $name = '', $then = '', $else = '' ) {
		if (isset($_GET[$name]) && $_GET[$name] != '')
			return $then;
		else
			return $else;
	}
}

function wfSetupExtGetVariables() {
	global $wgParser, $wgExtGetVariables, $wgHooks;

	$wgExtGetVariables = new ExtGetVariables;

	if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
		$wgHooks['ParserFirstCallInit'][] = array( &$wgExtGetVariables, 'registerParser' );
	} else {
		if ( class_exists( 'StubObject' ) && !StubObject::isRealObject( $wgParser ) ) {
			$wgParser->_unstub();
		}
		$wgExtGetVariables->registerParser( $wgParser );
	}
}

function wfGetVariablesLanguageGetMagic( &$magicWords, $langCode = 0 ) {
	$magicWords['getvar'] = array(0, 'getvar');
	$magicWords['ifgetvar'] = array(0, 'ifgetvar');
	return true;
}

?>
