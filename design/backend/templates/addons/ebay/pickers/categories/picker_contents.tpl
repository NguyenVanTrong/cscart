{if !$smarty.request.extra}
<script type="text/javascript">
(function(_, $) {
    _.tr('text_items_added', '{__("text_items_added")|escape:"javascript"}');
    var display_type = '{$smarty.request.display|escape:javascript nofilter}';

    $.ceEvent('on', 'ce.formpost_categories_form', function(frm, elm) {
        var categories = {};

        if ($('input.cm-item:checked', frm).length > 0) {
            $('input.cm-item:checked', frm).each( function() {
                var id = $(this).val();
                categories[id] = $('#category_' + $(this).data('id')).text();
            });

            {literal}
            $.cePicker('add_js_item', frm.data('caResultId'), categories, 'c', {
                '{category_id}': '%id',
                '{category}': '%item'
            });
            {/literal}

            if (display_type != 'radio') {
                $.ceNotification('show', {
                    type: 'N', 
                    title: _.tr('notice'), 
                    message: _.tr('text_items_added'), 
                    message_state: 'I'
                });
            }
        }

        return false;
    });
}(Tygh, Tygh.$));
</script>
{/if}

<div class="adv-search">
    <div class="group">
        <form action="{""|fn_url}" name="ebay_category_search_form" method="get" class="cm-disable-empty cm-ajax">
            <input type="hidden" value="{$company_id}" name="company_id">
            <input type="hidden" value="ebay_categories_picker" name="result_ids">
            <input type="hidden" value="{$smarty.request.data_id}" name="data_id">
            <input type="hidden" value="{$smarty.request.display}" name="display">
            <input type="hidden" value="{$smarty.request.get_tree}" name="get_tree">
            <input type="hidden" value="{$smarty.request.skip_result_ids_check}" name="skip_result_ids_check">
            {capture name="simple_search"}
                <div class="sidebar-field">
                    <label>{__("ebay_region")}</label>
                    <select name="site_id">
                        {foreach from=$ebay_sites item=site key=site_id}
                            <option {if $current_site_id == $site_id}selected="selected"{/if} value="{$site_id}">{$site}</option>
                        {/foreach}
                    </select>
                </div>
            {/capture}
            {include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search dispatch="ebay.categories_picker" in_popup=true}
        </form>
    </div>
</div>

<div id="ebay_categories_picker">
<form action="{$smarty.request.extra|fn_url}" data-ca-result-id="{$smarty.request.data_id}" method="post" name="categories_form">

<div class="items-container multi-level">
    {if $categories_tree}
        {include file="addons/ebay/views/ebay/components/categories_tree_simple.tpl" header=true checkbox_name=$smarty.request.checkbox_name|default:"categories_ids" parent_id=$category_id display=$smarty.request.display}
    {else}
        <p class="no-items center">
            {__("no_categories_available")}
        </p>
    {/if}
</div>

<div class="buttons-container">
    {if $smarty.request.display == "radio"}
        {assign var="but_close_text" value=__("choose")}
    {else}
        {assign var="but_close_text" value=__("add_categories_and_close")}
        {assign var="but_text" value=__("add_categories")}
    {/if}
    {include file="buttons/add_close.tpl" is_js=$smarty.request.extra|fn_is_empty}
</div>
</form>
<!--ebay_categories_picker--></div>