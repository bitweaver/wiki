{strip}
<div class="body"{if $gBitUser->getPreference( 'users_double_click' ) and $gContent->hasUpdatePermission()} ondblclick="location.href='{$smarty.const.WIKI_PKG_URL}edit.php?page_id={$pageInfo.page_id}';"{/if}>
	<div class="content">
		{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$gContent->mInfo}
		{$pageInfo.parsed_data}
		<div class="clear"></div>
		{if $gBitSystem->isFeatureActive( 'liberty_auto_display_attachment_thumbs' )}
			{include file="bitpackage:liberty/storage_thumbs.tpl"}
		{/if}
	</div> <!-- end .content -->
</div> <!-- end .body -->
{/strip}
