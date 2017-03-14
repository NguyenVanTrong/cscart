<div class="sidebar-row">
    <h6>{__("search")}</h6>
    <form action="{""|fn_url}" name="logs_form" method="get">
    <input type="hidden" name="object" value="{$smarty.request.object}">

    {capture name="simple_search"}
        <div class="sidebar-field">
            <label for="template_selects">{__("ebay_template")}:</label>
            <select id="template_selects" name="template_id">
                <option value=""{if !$search.action} selected="selected"{/if}>{__("all")}</option>
                {foreach from=$ebay_templates key=key item=name}
                    <option value="{$key}"{if $search.template_id == $key} selected="selected"{/if}>{$name}</option>
                {/foreach}
            </select>
        </div>
    {include file="common/period_selector.tpl" period=$search.period extra="" display="form" button="false"}
    {/capture}
    
    {capture name="advanced_search"}
    
    <div class="group form-horizontal">
        <div class="control-group">
            <label class="control-label">{__("action")}:</label>
            <div class="controls">
                <select id="q_action" name="action">
                    <option value=""{if !$search.action} selected="selected"{/if}>{__("all")}</option>
                    {foreach from=$ebay_actions key=key item=name}
                        <option value="{$key}"{if $search.action == $key} selected="selected"{/if}>{$name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">{__("type")}:</label>
            <div class="controls">
                <select id="q_type" name="type">
                    <option value=""{if !$search.type} selected="selected"{/if}>{__("all")}</option>
                    {foreach from=$ebay_types key=key item=name}
                        <option value="{$key}"{if $search.type == $key} selected="selected"{/if}>{$name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
    <div class="group form-horizontal">
        <div class="control-group">
            <label class="control-label">{__("log_type_products")}:</label>
            <div class="controls">
                {include file="pickers/products/picker.tpl" data_id="added_products" but_text=__("add") item_ids=$search.product_ids input_name="product_ids" type="links" no_container=true picker_view=true}
            </div>
        </div>
    </div>

    {/capture}
    
    {include file="common/advanced_search.tpl" advanced_search=$smarty.capture.advanced_search simple_search=$smarty.capture.simple_search dispatch="ebay.product_logs" view_type="ebay_product_logs"}
</form>
</div>
