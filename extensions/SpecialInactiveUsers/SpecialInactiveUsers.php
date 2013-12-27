<?php
/**
 * Implements Special:InactiveUsers
 *
 * Copyright © 2008 Aaron Schulz, 2013 Johannes Schindelin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SpecialPage
 */

require_once("$IP/includes/SpecialPage.php");

$wgSpecialPages['InactiveUsers'] = 'SpecialInactiveUsers';
$wgSpecialPageGroups['InactiveUsers'] = 'users';

/**
 * This class is used to get a list of inactive users. The ones with specials
 * rights (sysop, bureaucrat, developer) will have them displayed
 * next to their names.
 *
 * @ingroup SpecialPage
 */
class InactiveUsersPager extends UsersPager {

	/**
	 * @var FormOptions
	 */
	protected $opts;

	/**
	 * @var Array
	 */
	protected $groups;

	/**
	 * @var integer
	 */
	protected $days = 365;

	/**
	 * @var boolean
	 */
	protected $hideAuthenticated;

	/**
	 * @param $context IContextSource
	 * @param $group null Unused
	 * @param $par string Parameter passed to the page
	 */
	function __construct( IContextSource $context = null, $group = null, $par = null ) {
		parent::__construct( $context );

		$days = $this->getRequest()->getText( 'days', $par);
		if ( is_numeric( $days ) ) {
			$this->days = $days;
		}

		$this->RCMaxAge = $this->days;
		$un = $this->getRequest()->getText( 'username', $par );
		$this->requestedUser = '';
		if ( $un != '' ) {
			$username = Title::makeTitleSafe( NS_USER, $un );
			if( !is_null( $username ) ) {
				$this->requestedUser = $username->getText();
			}
		}

		$this->setupOptions();
	}

	public function setupOptions() {
		$isFirst = $this->getRequest()->getInt( 'days' ) == 0;
		$this->opts = new FormOptions();

		$this->opts->add( 'hideauthenticated', $isFirst, FormOptions::BOOL );
		$this->opts->add( 'hidebots', false, FormOptions::BOOL );
		$this->opts->add( 'hidesysops', false, FormOptions::BOOL );
		$this->opts->add( 'days', $this->days, FormOptions::INT );

		$this->opts->fetchValuesFromRequest( $this->getRequest() );

		$this->groups = array();
		$this->hideAuthenticated = $this->opts->getValue( 'hideauthenticated' ) == 1;
		if ( $this->opts->getValue( 'hidebots' ) == 1 ) {
			$this->groups['bot'] = true;
		}
		if ( $this->opts->getValue( 'hidesysops' ) == 1 ) {
			$this->groups['sysop'] = true;
		}
		$this->days = $this->opts->getValue( 'days' );
	}

	function getQueryInfo() {
		$dbr = wfGetDB( DB_SLAVE );
		$conds = array( );
		$conds[] = "0 =  (SELECT COUNT(rev_id) FROM revision WHERE rev_user = user_id "
			. "AND (rev_timestamp >= '{$dbr->timestamp( wfTimestamp( TS_UNIX ) - $this->RCMaxAge*24*3600 )}'))";

		if( $this->requestedUser != '' ) {
			$conds[] = 'user_name >= ' . $dbr->addQuotes( $this->requestedUser );
		}

		if ( $this->hideAuthenticated ) {
			$conds[] = 'user_email_authenticated IS NULL OR ipb_by_text IS NOT NULL';
		}

		$query = array(
			'tables' => array( 'user', 'ipblocks' ),
			'fields' => array( 'user_id', 'user_name', 'user_registration', 'user_email_authenticated',
				'ipb_by_text'
			),
			'options' => array(
				'GROUP BY' => 'user_id',
			),
			'join_conds' => array(
				'ipblocks' => array( 'LEFT JOIN', 'user_id=ipb_user' ),
			),
			'conds' => $conds
		);
		return $query;
	}

	function formatRow( $row ) {
		$userName = $row->user_name;

		$ulinks = Linker::userLink( $row->user_id, $userName );
		$ulinks .= Linker::userToolLinks( $row->user_id, $userName );

		$lang = $this->getLanguage();

		$list = array();
		foreach( self::getGroups( $row->user_id ) as $group ) {
			if ( isset( $this->groups[$group] ) ) {
				return '';
			}
			$list[] = self::buildGroupLink( $group, $userName );
		}
		$groups = $lang->commaList( $list );

		$item = $lang->specialList( $ulinks, $groups );
		$blocked = $row->ipb_by_text ? ' blocked by ' . htmlentities( $row->ipb_by_text ) : '';
		global $wgLang;
		$notyetauthenticated = $row->user_email_authenticated ? '' : ' not yet authenticated (registered on ' . $wgLang->timeanddate( $row->user_registration ) . ')';

		$userMergeLink = '';
		if ( ! $row->user_email_authenticated && $userName != 'Spam' && $GLOBALS[ 'wgUser' ]->isAllowed( 'usermerge' ) ) {
			global $wgServer, $wgArticlePath;
			$userMergeLink = '<a href="' . $wgServer .
				preg_replace( '/\$1/', 'Special:UserMerge', $wgArticlePath ) .
				'?olduser=' . htmlentities( $userName ) . '&newuser=Spam&deleteuser=true">Merge with <i>Spam</i> account</a>';
		}

		return Html::rawElement( 'li', array(), "{$item} {$blocked} {$notyetauthenticated} {$userMergeLink}" );
	}

	function getPageHeader() {
		global $wgScript;

		$self = $this->getTitle();
		$limit = $this->mLimit ? Html::hidden( 'limit', $this->mLimit ) : '';

		$out = Xml::openElement( 'form', array( 'method' => 'get', 'action' => $wgScript ) ); # Form tag
		$out .= Xml::fieldset( "Inactive users" ) . "\n";
		$out .= Html::hidden( 'title', $self->getPrefixedDBkey() ) . $limit . "\n";

		$out .= Xml::inputLabel( "Starting from user",
			'username', 'offset', 20, $this->requestedUser ) . '<br />';# Username field

		$out .= Xml::inputLabel( "Days",
			'days', 'days', 4, $this->days ) . '<br />';# Days field


		$out .= Xml::checkLabel( "Hide authenticated users",
			'hideauthenticated', 'hideauthenticated', $this->opts->getValue( 'hideauthenticated' ) );

		$out .= Xml::checkLabel( "Hide bots",
			'hidebots', 'hidebots', $this->opts->getValue( 'hidebots' ) );

		$out .= Xml::checkLabel( "Hide sysops",
			'hidesysops', 'hidesysops', $this->opts->getValue( 'hidesysops' ) ) . '<br />';

		$out .= Xml::submitButton( $this->msg( 'allpagessubmit' )->text() ) . "\n";# Submit button and form bottom
		$out .= Xml::closeElement( 'fieldset' );
		$out .= Xml::closeElement( 'form' );

		return $out;
	}
}

/**
 * @ingroup SpecialPage
 */
class SpecialInactiveUsers extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'InactiveUsers', 'block' );
	}

	function getDescription() {
		return "InactiveUsers";
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();

		$up = new InactiveUsersPager( $this->getContext(), null, $par );

		# getBody() first to check, if empty
		$usersbody = $up->getBody();

		$out->addHTML( $up->getPageHeader() );
		if ( $usersbody ) {
			$out->addHTML(
				$up->getNavigationBar() .
				Html::rawElement( 'ul', array(), $usersbody ) .
				$up->getNavigationBar()
			);
		} else {
			$out->addHTML( "No matching records" );
		}
	}

}
