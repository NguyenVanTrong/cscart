{if $show_rating && in_array($addons.discussion.company_discussion_type, ['B', 'R'])}

    {if $company.average_rating}
        {$average_rating = $company.average_rating}
    {elseif $company.discussion.average_rating}
        {$average_rating = $company.discussion.average_rating}
    {/if}

    {if $average_rating > 0}
        {include file="addons/discussion/views/discussion/components/stars.tpl"
            stars=$average_rating|fn_get_discussion_rating
            link="companies.view?company_id={$company.company_id}&selected_section=discussion#discussion"
        }
    {/if}

{/if}