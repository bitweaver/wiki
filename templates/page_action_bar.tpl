{if $print_page ne 'y'}
<div class="navbar">
	<ul>
		{if $gBitUser->hasPermission( 'p_users_view_icons_and_tools' )}
			{if !$lock}
				{assign var=format_guid value=$pageInfo.format_guid}
				{if $gLibertySystem->mPlugins.$format_guid.is_active eq 'y'}
					{if $gContent->hasUserPermission( 'p_wiki_edit_page' ) or $page eq 'SandBox'}
						<li><a {if $beingEdited eq 'y'}class="highlight" title="{$semUser}"{/if} href="{$smarty.const.WIKI_PKG_URL}edit.php?page_id={$pageInfo.page_id}">{tr}Edit{/tr}</a></li>
					{/if}
				{/if}
			{/if}
			{if $page ne 'SandBox'}
				{if $gBitUser->hasPermission( 'p_wiki_admin' ) or ($gBitUser->mUserId and ($gBitUser->mUserId eq $pageInfo.modifier_user_id) and ($gBitUser->hasPermission( 'p_wiki_lock_page' )) and ($gBitSystem->isFeatureActive( 'wiki_usrlock' )))}
					{if $lock}
						<li><a href="{$smarty.const.WIKI_PKG_URL}index.php?page_id={$pageInfo.page_id}&amp;action=unlock">{tr}Unlock{/tr}</a></li>
					{else}
						<li><a href="{$smarty.const.WIKI_PKG_URL}index.php?page_id={$pageInfo.page_id}&amp;action=lock">{tr}Lock{/tr}</a></li>
					{/if}
				{/if}
				{if $gBitUser->hasPermission( 'p_wiki_admin' )}
					<li><a href="{$smarty.const.WIKI_PKG_URL}page_permissions.php?page_id={$pageInfo.page_id}">{tr}Permissions{/tr}</a></li>
				{/if}
				{if $gBitSystem->isFeatureActive( 'wiki_history' ) and $gContent->hasUserPermission('p_wiki_view_history')}
					<li><a href="{$smarty.const.WIKI_PKG_URL}page_history.php?page_id={$pageInfo.page_id}">{tr}History{/tr}</a></li>
				{/if}
			{/if}
			{if $gBitSystem->isFeatureActive( 'wiki_like_pages' )}
				<li><a href="{$smarty.const.WIKI_PKG_URL}like_pages.php?page_id={$pageInfo.page_id}">{tr}Similar{/tr}</a></li>
			{/if}
			{if $gBitSystem->isFeatureActive( 'wiki_undo' ) and !$gContent->isLocked() and $gContent->hasUserPermission('p_wiki_rollback')}
				<li><a href="{$smarty.const.WIKI_PKG_URL}index.php?page_id={$pageInfo.page_id}&amp;undo=1">{tr}Undo{/tr}</a></li>
			{/if}
			{if $gBitSystem->isFeatureActive( 'wiki_uses_slides' )}
				{if $show_slideshow eq 'y'}
					<li><a href="{$smarty.const.WIKI_PKG_URL}slideshow.php?page_id={$pageInfo.page_id}">{tr}Slides{/tr}</a></li>
				{elseif $structure eq 'y'}
					<li><a href="slideshow2.php?structure_id={$page_info.structure_id}">{tr}Slides{/tr}</a></li>
				{/if}
			{/if}
			{if $gBitUser->hasPermission( 'p_wiki_admin' )}
				<li><a href="{$smarty.const.WIKI_PKG_URL}export_wiki_pages.php?page_id={$pageInfo.page_id}">{tr}Export{/tr}</a></li>
			{/if}
			{if $gBitSystem->isFeatureActive( 'wiki_discuss' )}
				<li><a href="{$smarty.const.BITFORUMS_PKG_URL}view_forum.php?forum_id={$wiki_forum_id}&amp;comments_postComment=post&amp;comments_title={$page|escape:"url"}&amp;comments_data={ "Use this thread to discuss the [index.php\?page=$page|$page page."|escape:"url"}&amp;comment_topictype=n">{tr}Discuss{/tr}</a></li>
			{/if}
		{/if}
	</ul>
</div>
{/if}
