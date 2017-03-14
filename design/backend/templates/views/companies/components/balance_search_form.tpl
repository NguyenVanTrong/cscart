<div class="sidebar-row">
<h6>{__("search")}</h6>

<form action="{""|fn_url}" name="balance_search_form" method="get" class="cm-disable-empty">
{capture name="simple_search"}

{if $smarty.request.redirect_url}
    <input type="hidden" name="redirect_url" value="{$smarty.request.redirect_url}" />
{/if}
{if $smarty.request.selected_section != ""}
    <input type="hidden" id="selected_section" name="selected_section" value="{$smarty.request.selected_section}" />
{/if}

<div class="sidebar-field ajax-select">
    <label>{__("vendor")}</label>
    {if !$runtime.company_id}
        <input type="hidden" name="vendor" id="search_hidden_vendor" value="{$search.vendor|default:'all'}" />
    {include file="common/ajax_select_object.tpl" data_url="companies.get_companies_list?show_all=Y" text=$search.vendor|fn_get_company_name|default:__("all_vendors") result_elm="search_hidden_vendor" id="company_search"}
        {else}
        {$search.vendor|fn_get_company_name}
    {/if}
</div>

{if $payout_types}
    <div class="sidebar-field">
        <label>{__("vendor_payouts.type")}:</label>
        <select name="payout_type">
            <option value="">{__("all")}</option>
            {foreach $payout_types as $type_id}
                <option value="{$type_id}"{if $smarty.request.payout_type == $type_id} selected="selected"{/if}>{__("vendor_payouts.type.{$type_id}")}</option>
            {/foreach}
        </select>
    </div>
{/if}


<div class="sidebar-field">
    <label>{__("vendor_payouts.approval_status")}:</label>
    <select name="approval_status">
        <option value="">{__("all")}</option>
        {foreach $approval_statuses as $status_id => $status_description}
            <option value="{$status_id}"{if $smarty.request.approval_status == $status_id} selected="selected"{/if}>{$status_description}</option>
        {/foreach}
    </select>
</div>

<div class="sidebar-field">
    {include file="common/period_selector.tpl" period=$search.period form_name="balance_search_form" display="form"}
</div>

{/capture}

{include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search no_adv_link=true dispatch=$dispatch view_type="balance"}

</form>
</div>
<hr>