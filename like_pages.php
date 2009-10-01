<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_wiki/like_pages.php,v 1.16 2009/10/01 13:45:54 wjames5 Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * $Id: like_pages.php,v 1.16 2009/10/01 13:45:54 wjames5 Exp $
 * @package wiki
 * @subpackage functions
 */

/**
 * required setup
 */
require_once( '../bit_setup_inc.php' );
include_once( WIKI_PKG_PATH.'BitPage.php');
include_once( WIKI_PKG_PATH.'lookup_page_inc.php' );
$gBitSystem->verifyPackage( 'wiki' );
$gBitSystem->verifyFeature( 'wiki_like_pages' );
$gBitSystem->verifyPermission( 'p_wiki_list_pages' );

// Get the page from the request var or default it to HomePage
if( !$gContent->isValid() ) {
	$gBitSystem->fatalError( tra( "No page indicated" ));
}

$likepages = $gContent->getLikePages( $gContent->mInfo['title'] );
$gBitSmarty->assign_by_ref( 'likepages', $likepages );

// Display the template
$gBitSystem->display( 'bitpackage:wiki/like_pages.tpl', NULL, array( 'display_mode' => 'display' ));
?>
