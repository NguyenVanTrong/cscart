{capture name="mainbox"}
<form action="{""|fn_url}" method="post" name="ebay_templates_form" class="">
<input type="hidden" name="fake" value="1" />

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{assign var="return_current_url" value=$config.current_url|escape:url}
{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{assign var="c_icon" value="<i class=\"icon-`$search.sort_order_rev`\"></i>"}
{assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}

{if $templates}
<table width="100%" class="table table-middle">
<thead>
<tr>
    <th width="1%" class="left">
        {include file="common/check_items.tpl"}</th>
    <th width="45%"><a class="cm-ajax" href="{"`$c_url`&sort_by=template&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("name")}{if $search.sort_by == "template"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=products&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("products")}{if $search.sort_by == "products"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
    <th width="10%"></th>
    <th width="10%" class="right"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{if $search.sort_by == "status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}{__("status")}</a></th>
</tr>
</thead>
{foreach from=$templates item=template}
<tr class="cm-row-status-{$template.status|lower}">
    {assign var="allow_save" value=$template|fn_allow_save_object:"ebay_templates"}
    {if $allow_save}
        {assign var="no_hide_input" value="cm-no-hide-input"}
        {assign var="display" value=""}
    {else}
        {assign var="no_hide_input" value=""}
        {assign var="display" value="text"}
    {/if}
    <td class="left">
        <input type="checkbox" name="template_ids[]" value="{$template.template_id}" class="cm-item" /></td>
    <td class="row-status">
        <a href="{"ebay.update?template_id=`$template.template_id`"|fn_url}">{$template.name}</a>
        {include file="views/companies/components/company_name.tpl" object=$template}
    </td>
    <td>
        <a href="{"products.manage?ebay_template_id=`$template.template_id`"|fn_url}">{$template.product_count}</a>
    </td>
    <td class="nowrap right">
        <div class="hidden-tools">
            {capture name="tools_items"}
                <li>{btn type="list" class="cm-ajax cm-comet" text=__("export_products_to_ebay") href="ebay.export_template?template_id=`$template.template_id`" method="POST"}</li>
                <li>{btn type="list" class="cm-ajax cm-comet" text=__("ebay_end_template_on_ebay") href="ebay.end_template?template_id=`$template.template_id`" method="POST"}</li>
                <li>{btn type="list" class="cm-ajax cm-comet" text=__("ebay_sync_products_status") href="ebay.update_template_product_status?template_id=`$template.template_id`" method="POST"}</li>
                <li>{btn type="list" text=__("logs") href="ebay.product_logs?template_id=`$template.template_id`"}</li>
                <li class="divider"></li>
                <li>{btn type="list" text=__("edit") href="ebay.update?template_id=`$template.template_id`"}</li>
                <li>{btn type="list" class="cm-confirm" data=["data-ca-confirm-text" => "{__("category_deletion_side_effects")}"] text=__("delete") href="ebay.delete_template?template_id=`$template.template_id`" method="POST"}</li>
            {/capture}
            {dropdown content=$smarty.capture.tools_items}
        </div>
    </td>
    <td class="right nowrap">
        {include file="common/select_popup.tpl" popup_additional_class="dropleft `$no_hide_input`" display=$display id=$template.template_id status=$template.status hidden=false object_id_name="template_id" table="ebay_templates"}
    </td>
</tr>
{/foreach}
</table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}
</form>

{capture name="adv_buttons"}
    {include file="common/tools.tpl" tool_href="ebay.add" prefix="top" hide_tools="true" title=__("add_ebay_template") icon="icon-plus"}
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        {if $templates}
            <li>{btn type="list" text=__("delete_selected") class="cm-confirm" dispatch="dispatch[ebay.m_delete]" form="ebay_templates_form"}</li>
        {/if}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("ebay_templates") content=$smarty.capture.mainbox  buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons}
