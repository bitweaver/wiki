<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_wiki/display_bitpage_inc.php,v 1.1.1.1.2.2 2005/06/29 07:09:12 jht001 Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: display_bitpage_inc.php,v 1.1.1.1.2.2 2005/06/29 07:09:12 jht001 Exp $
 * @package wiki
 * @subpackage functions
 */

/**
 * required setup
 */
include_once( WIKI_PKG_PATH.'BitBook.php');

if (defined("CATEGORIES_PKG_PATH")) {
	include_once( CATEGORIES_PKG_PATH.'categ_lib.php');
}

$gBitSystem->verifyPackage( 'wiki' );

$gBitSystem->verifyPermission( 'bit_p_view' );

// Check permissions to access this page
if( !$gContent->isValid() ) {
	$gBitSystem->fatalError( 'Page cannot be found' );
}

/*
$smarty->assign('structure','n');
//Has a structure page been requested
if (isset($_REQUEST["structure_id"])) {
	$structure_id = $_REQUEST["structure_id"];
} else {
	//if not then check if page is the head of a structure
	$structure_id = $structlib->get_struct_ref_if_head( $gContent->mPageName );
}
$smarty->assign_by_ref('page',$gContent->mInfo['title']);
*/

require_once( WIKI_PKG_PATH.'page_setup_inc.php' );
// Let creator set permissions
if($wiki_creator_admin == 'y') {
  if( $gContent->isOwner() ) {
    $bit_p_admin_wiki = 'y';
    $smarty->assign( 'bit_p_admin_wiki', 'y' );
  }
}
if(isset($_REQUEST["copyrightpage"])) {
  $smarty->assign_by_ref('copyrightpage',$_REQUEST["copyrightpage"]);
}
if( $gBitSystem->isFeatureActive( 'feature_backlinks' ) ) {
	// Get the backlinks for the page "page"
	$backlinks = $gContent->getBacklinks();
	$smarty->assign_by_ref('backlinks', $backlinks);
}

// Update the pagename with the canonical name.  This makes it
// possible to link to a page using any case, but the page is still
// displayed with the original capitalization.  So if there's a page
// called 'About Me', then one can conveniently make a link to it in
// the text as '... learn more ((about me)).'.  When the link is
// followed,
$gBitSystem->setBrowserTitle( $gContent->mInfo['title'] );
// BreadCrumbNavigation here
// Get the number of pages from the default or userPreferences
// Remember to reverse the array when posting the array
$anonpref = $wikilib->getPreference('userbreadCrumb',4);
if(!empty($user)) {
  $userbreadCrumb = $gBitUser->getPreference( 'userbreadCrumb', $anonpref );
} else {
  $userbreadCrumb = $anonpref;
}
if(!isset($_SESSION["breadCrumb"])) {
  $_SESSION["breadCrumb"]=Array();
}
if(!in_array($gContent->mInfo['title'],$_SESSION["breadCrumb"])) {
  if(count($_SESSION["breadCrumb"])>$userbreadCrumb) {
    array_shift($_SESSION["breadCrumb"]);
  }
  array_push($_SESSION["breadCrumb"],$gContent->mInfo['title']);
} else {
  // If the page is in the array move to the last position
  $pos = array_search($gContent->mInfo['title'], $_SESSION["breadCrumb"]);
  unset($_SESSION["breadCrumb"][$pos]);
  array_push($_SESSION["breadCrumb"],$gContent->mInfo['title']);
}
//print_r($_SESSION["breadCrumb"]);
// Now increment page hits since we are visiting this page
if($count_admin_pvs == 'y' || !$gBitUser->isAdmin()) {
  $gContent->addHit();
}
// Check if we have to perform an action for this page
// for example lock/unlock
if( isset( $_REQUEST["action"] ) && (($_REQUEST["action"] == 'lock' || $_REQUEST["action"]=='unlock' ) &&
	($gBitUser->hasPermission( 'bit_p_admin_wiki' )) || ($user and ($gBitUser->hasPermission( 'bit_p_lock' )) and ($feature_wiki_usrlock == 'y'))) ) {
	$gContent->setLock( ($_REQUEST["action"] == 'lock' ? 'L' : NULL ) );
	$smarty->assign('lock', ($_REQUEST["action"] == 'lock') );
}


// Save to notepad if user wants to
if( $gBitSystem->isPackageActive( 'notepad' ) && $gBitUser->isValid() && $gBitUser->hasPermission( 'bit_p_notepad' ) && isset($_REQUEST['savenotepad'])) {
	
	require_once( NOTEPAD_PKG_PATH.'notepad_lib.php' );
	$notepadlib->replace_note( $user, 0, $gContent->mPageName, $gContent->mInfo['data'] );
}
// Assign lock status
$smarty->assign('lock', $gContent->isLocked() );
// If not locked and last version is user version then can undo
$smarty->assign('canundo','n');
if( !$gContent->isLocked() && ( ($gBitUser->hasPermission( 'bit_p_edit' ) == 'y' && $gContent->mInfo["modifier_user_id"]==$gBitUser->mUserId) || $gBitUser->hasPermission( 'bit_p_remove' ) ) ) {
   $smarty->assign('canundo','y');
}
if($gBitUser->hasPermission( 'bit_p_admin_wiki' )) {
  $smarty->assign('canundo','y');
}
// Process an undo here
if(isset($_REQUEST["undo"])) {
	
	if($gBitUser->hasPermission( 'bit_p_admin_wiki' ) || ($gContent->mInfo["flag"]!='L' && ( ($gBitUser->hasPermission( 'bit_p_edit' ) && $gContent->mInfo["user"]==$user)||($bit_p_remove=='y')) )) {
		// Remove the last version
		$gContent->removeLastVersion();
		// If page was deleted then re-create
		if( !$fPID ) {
			$wikilib->create_page($gContent->mInfo['title'],0,'',date("U"),'Tiki initialization');
		}
	}
}
if ($wiki_uses_slides == 'y') {
	$slides = split("-=[^=]+=-",$gContent->mInfo["data"]);
	if(count($slides)>1) {
		$smarty->assign('show_slideshow','y');
	} else {
		$slides = explode(defined('PAGE_SEP') ? PAGE_SEP : "...page...",$gContent->mInfo["data"]);
		if(count($slides)>1) {
			$smarty->assign('show_slideshow','y');
		} else {
			$smarty->assign('show_slideshow','n');
		}
	}
} else {
	$smarty->assign('show_slideshow','n');
}
if(isset($_REQUEST['refresh'])) {
	
  $wikilib->invalidate_cache($gContent->mInfo['title']);
}
// Here's where the data is parsed
// if using cache
//
// get cache information
// if cache is valid then pdata is cache
// else
// pdata is parse_data
//   if using cache then update the cache
// assign_by_ref
$smarty->assign('cached_page','n');
if(isset($gContent->mInfo['wiki_cache']) && $gContent->mInfo['wiki_cache']>0) {
	$wiki_cache=$gContent->mInfo['wiki_cache'];
}
if($wiki_cache>0) {
	$cache_info = $wikilib->get_cache_info($gContent->mInfo['title']);
	$now = date('U');
	if($cache_info['cache_timestamp']+$wiki_cache > $now) {
		$pdata = $cache_info['cache'];
		$smarty->assign('cached_page','y');
	} else {
		$pdata = $gContent->parseData();
		$gContent->updateCache( $pdata );
	}
} else {
	$pdata = $gContent->parseData();
}
$pages = $wikilib->countPages($pdata);
if( $pages > 1 ) {
	if(!isset($_REQUEST['pagenum'])) {
		$_REQUEST['pagenum']=1;
	}
	$pdata=$wikilib->get_page($pdata,$_REQUEST['pagenum']);
	$smarty->assign('pages',$pages);
	if($pages>$_REQUEST['pagenum']) {
		$smarty->assign('next_page',$_REQUEST['pagenum']+1);
	} else {
		$smarty->assign('next_page',$_REQUEST['pagenum']);
	}
	if($_REQUEST['pagenum']>1) {
		$smarty->assign('prev_page',$_REQUEST['pagenum']-1);
	} else {
		$smarty->assign('prev_page',1);
	}
	$smarty->assign('first_page',1);
	$smarty->assign('last_page',$pages);
	$smarty->assign('pagenum',$_REQUEST['pagenum']);
}

$smarty->assign_by_ref('parsed',$pdata);
//$smarty->assign_by_ref('last_modified',date("l d of F, Y  [H:i:s]",$gContent->mInfo["last_modified"]));
$smarty->assign_by_ref('last_modified',$gContent->mInfo["last_modified"]);
if(empty($gContent->mInfo["user"])) {
  $gContent->mInfo["user"]='anonymous';
}
$smarty->assign_by_ref('lastUser',$gContent->mInfo["user"]);
$smarty->assign_by_ref('description',$gContent->mInfo["description"]);

// Comments engine!
if( $gBitSystem->isFeatureActive( 'feature_wiki_comments' ) ) {

  // if user requested a comment display option, then assume they want to see the comments,
  // so display those first.
  
  $comments_at_top_of_page = 'n';
  
  $maxComments = $gBitSystem->getPreference( 'wiki_comments_per_page', 10 );
  if (!empty($_REQUEST["comments_maxComments"])) {
    $maxComments = $_REQUEST["comments_maxComments"];
    $comments_at_top_of_page = 'y';
  }
  $comments_sort_mode = $gBitSystem->getPreference( 'wiki_comments_default_ordering' );
  if (!empty($_REQUEST["comments_sort_mode"])) {
    $comments_sort_mode = $_REQUEST["comments_sort_mode"];
    $comments_at_top_of_page = 'y';
  }

  $comments_display_style = 'flat';
  if (!empty($_REQUEST["comments_style"])) {
    $comments_display_style = $_REQUEST["comments_style"];
    $comments_at_top_of_page = 'y';
  }

  $comments_vars=Array('page');
  $comments_prefix_var='wiki page:';
  $comments_object_var='page';
  $commentsParentId = $gContent->mContentId;
  $comments_return_url = WIKI_PKG_URL.'index.php?page_id='.$gContent->mPageId;
  include_once( LIBERTY_PKG_PATH.'comments_inc.php' );
}
$section='wiki';
if( $gBitSystem->isFeatureActive( 'feature_wiki_attachments' ) ) {
  if(isset($_REQUEST["removeattach"])) {
		
    $owner = $wikilib->get_attachment_owner($_REQUEST["removeattach"]);
    if( ($user && ($owner == $user) ) || ($gBitUser->hasPermission( 'bit_p_wiki_admin_attachments' )) ) {
      $wikilib->remove_wiki_attachment($_REQUEST["removeattach"]);
    }
  }
  if(isset($_REQUEST["attach"]) && ($gBitUser->hasPermission( 'bit_p_wiki_admin_attachments' ) || $gBitUser->hasPermission( 'bit_p_wiki_attach_files' ))) {
		
    // Process an attachment here
    if(isset($_FILES['userfile1'])&&is_uploaded_file($_FILES['userfile1']['tmp_name'])) {
      $fp = fopen($_FILES['userfile1']['tmp_name'],"rb");
      $data = '';
      $fhash='';
      if($w_use_db == 'n') {
        $fhash = md5($name = $_FILES['userfile1']['name']);
        $fw = fopen($w_use_dir.$fhash,"wb");
        if(!$fw) {
          $smarty->assign('msg',tra('Cannot write to this file:').$fhash);
          $gBitSystem->display( 'error.tpl' );
          die;
        }
      }
      while(!feof($fp)) {
        if($w_use_db == 'y') {
          $data .= fread($fp,8192*16);
        } else {
          $data = fread($fp,8192*16);
          fwrite($fw,$data);
        }
      }
      fclose($fp);
      if($w_use_db == 'n') {
        fclose($fw);
        $data='';
      }
      $size = $_FILES['userfile1']['size'];
      $name = $_FILES['userfile1']['name'];
      $type = $_FILES['userfile1']['type'];
      $wikilib->wiki_attach_file($gContent->mInfo['title'],$name,$type,$size, $data, $_REQUEST["attach_comment"], $user,$fhash);
    }
  }
  $smarty->assign('atts',$gContent->mStorage);
  $smarty->assign('atts_count',count($gContent->mStorage));
}

if( $gBitSystem->isFeatureActive( 'feature_wiki_footnotes' ) && $gBitUser->isValid() ) {
	if( $footnote = $gContent->getFootnote( $gBitUser->mUserId ) ) {
		$smarty->assign( 'footnote', $gContent->parseData( $footnote ) );
	}
}

if( $gBitSystem->isFeatureActive( 'wiki_feature_copyrights' ) ) {
	require_once( WIKI_PKG_PATH.'copyrights_lib.php' );
	$copyrights = $copyrightslib->list_copyrights( $gContent->mPageId );
	$smarty->assign('pageCopyrights', $copyrights["data"]);
}

$smarty->assign('wiki_extras','y');
if( $gBitSystem->isFeatureActive( 'feature_theme_control' ) ) {
	$cat_type=BITPAGE_CONTENT_TYPE_GUID;
	$cat_objid = $gContent->mContentId;
	include( THEMES_PKG_PATH.'tc_inc.php' );
}
// Watches
if( $gBitSystem->isFeatureActive( 'feature_user_watches' ) ) {
	if( isset( $_REQUEST['watch_event'] ) ) {
		if( $gBitUser->isRegistered() ) {
			if($_REQUEST['watch_action']=='add') {
				$gBitUser->storeWatch( $_REQUEST['watch_event'], $_REQUEST['watch_object'], $gContent->mContentTypeGuid, $gContent->mPageName, $gContent->getDisplayUrl() );
			} else {
				$gBitUser->expungeWatch( $_REQUEST['watch_event'], $_REQUEST['watch_object'] );
			}
		} else {
			$smarty->assign('msg', tra("This feature requires a registered user.").": feature_user_watches");
			$gBitSystem->display( 'error.tpl' );
			die;
		}
	}
	$smarty->assign('user_watching_page','n');
	if( $watch = $gBitUser->getEventWatches( 'wiki_page_changed', $gContent->mPageId ) ) {
		$smarty->assign('user_watching_page','y');
	}
}
$sameurl_elements=Array('title','page');
//echo $gContent->mInfo["data"];
if(isset($_REQUEST['mode']) && $_REQUEST['mode']=='mobile') {
/*
	require_once(HAWHAW_PKG_PATH."hawhaw.inc");
	require_once(HAWHAW_PKG_PATH."hawiki_cfg.inc");
	require_once(HAWHAW_PKG_PATH."hawiki_parser.inc");
	require_once(HAWHAW_PKG_PATH."hawiki.inc");
	error_reporting(E_ALL & ~E_NOTICE);
	$myWiki = new HAWIKI_page($gContent->mInfo["data"], WIKI_PKG_URL."index.php?mode=mobile&page=");
	$myWiki->set_navlink(tra("Home Page"), WIKI_PKG_URL."index.php?mode=mobile", HAWIKI_NAVLINK_TOP | HAWIKI_NAVLINK_BOTTOM);
	$myWiki->set_navlink(tra("Menu"), HAWHAW_PKG_URL."mobile.php", HAWIKI_NAVLINK_TOP | HAWIKI_NAVLINK_BOTTOM);
	$myWiki->set_smiley_dir("img/smiles");
	$myWiki->set_link_jingle(HAWHAW_PKG_PATH."link.wav");
	$myWiki->set_hawimconv(HAWHAW_PKG_PATH."hawimconv.php");
	$myWiki->display();
	die;
  */
  include_once( HAWHAW_PKG_PATH."hawtiki_lib.php" );
  HAWBIT_index($gContent->mInfo);
}
if( $gBitSystem->isPackageActive( 'categories' ) ) {
	// Check to see if page is categorized
	$cat_objid = $gContent->mContentId;
	$cat_obj_type = BITPAGE_CONTENT_TYPE_GUID;
	include_once( CATEGORIES_PKG_PATH.'categories_display_inc.php' );
}
// Flag for 'page bar' that currently 'Page view' mode active
// so it is needed to show comments & attachments panels
$smarty->assign('show_page','y');

// Display the Index Template
$smarty->assign('dblclickedit','y');
$smarty->assign('print_page','n');
$smarty->assign('show_page_bar','y');
$smarty->assign_by_ref( 'pageInfo', $gContent->mInfo );

if( isset( $_REQUEST['s5'] ) ) {
	include_once( WIKI_PKG_PATH.'s5.php');
}

$gBitSystem->display('bitpackage:wiki/show_page.tpl');
// xdebug_dump_function_profile(XDEBUG_PROFILER_CPU);
?>
