<?php
/**
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * @package wiki
 * @subpackage functions
 */

/**
 * required setup
 */
require_once( '../kernel/includes/setup_inc.php' );
include_once( WIKI_PKG_CLASS_PATH.'BitPage.php');
include_once( WIKI_PKG_INCLUDE_PATH.'lookup_page_inc.php' );
$gBitSystem->verifyPackage( 'wiki' );
$gBitSystem->verifyFeature( 'wiki_like_pages' );
$gBitSystem->verifyPermission( 'p_wiki_list_pages' );

// Get the page from the request var or default it to HomePage
if( !$gContent->isValid() ) {
	$gBitSystem->fatalError( tra( "No page indicated" ));
}

$likepages = $gContent->getLikePages( $gContent->mInfo['title'] );
$gBitSmarty->assignByRef( 'likepages', $likepages );

// Display the template
$gBitSystem->display( 'bitpackage:wiki/like_pages.tpl', NULL, array( 'display_mode' => 'display' ));
?>
