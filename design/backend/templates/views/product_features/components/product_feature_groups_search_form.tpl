<div class="sidebar-row">
<h6>{__("search")}</h6>

<form action="{""|fn_url}" name="product_features_search_form" method="get">

<div class="sidebar-field">
    <label>{__("category")}:</label>
    <div class="break clear correct-picker-but">
    {if "categories"|fn_show_picker:$smarty.const.CATEGORY_THRESHOLD}
        {if $search.category_ids}
            {assign var="s_cid" value=$search.category_ids}
        {else}
            {assign var="s_cid" value="0"}
        {/if}
        {include file="pickers/categories/picker.tpl" data_id="location_category" input_name="category_ids" item_ids=$s_cid hide_link=true hide_delete_button=true default_name=__("all_categories") extra=""}
    {else}
        {include file="common/select_category.tpl" name="category_ids" id=$search.category_ids}
    {/if}
    </div>
</div>
<div class="sidebar-field">
    <label for="fname">{__("group")}:</label>
    <input type="text" name="description" id="fname" value="{$search.description}" size="30" />
</div>
<div class="sidebar-field">
    <input class="btn" type="submit" name="dispatch[{$dispatch}]" value="{__("search")}">
</div>
</form>
</div>