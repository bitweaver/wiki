{strip}
{if !$gBitSystem->isFeatureActive( 'wiki_hide_date' )}
	<div class="date">
		{tr}Created by{/tr}: {displayname user=$pageInfo.creator_user user_id=$pageInfo.user_id real_name=$pageInfo.creator_real_name},&nbsp;
		{tr}Last modification{/tr}: {$pageInfo.last_modified|reltime}
		{if $pageInfo.modifier_user_id!=$pageInfo.user_id}
			&nbsp;
			{tr}by{/tr} {displayname user=$pageInfo.modifier_user user_id=$pageInfo.modifier_user_id real_name=$pageInfo.modifier_real_name}
		{/if}
	</div>
{/if}
{/strip}
