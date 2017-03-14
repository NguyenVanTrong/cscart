{include file="common/letter_header.tpl"}

{__("dear")} {$user_data.firstname},<br /><br />

{__("we_would_like_to_inform")}: {if $reason.action == 'A'}{__("reward_points_subj_added_to", [$reason.amount])}{else}{__("reward_points_subj_subtracted_from", [$reason.amount])}{/if}<br />

<b>{__("reason")}:</b><br />
        {$reason.reason}

{include file="common/letter_footer.tpl"}