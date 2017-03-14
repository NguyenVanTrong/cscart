{script src="js/tygh/tabs.js"}

{capture name="mainbox"}

    {include file="common/pagination.tpl"}

    {assign var="r_url" value=$config.current_url|escape:url}

    <form action="{""|fn_url}" method="post" name="manage_product_features_form" id="manage_product_features_form">
    <input type="hidden" name="return_url" value="{$config.current_url}">
    <div class="items-container{if ""|fn_check_form_permissions} cm-hide-inputs{/if}" id="update_features_list">
        {if $features}
            <table width="100%" class="table table-middle table-objects">
                <thead>
                <tr>
                    <th class="left" width="1%">
                        {include file="common/check_items.tpl" check_statuses=""|fn_get_default_status_filters:true}
                    </th>
                    <th width="20%">{__("feature")}</th>
                    <th width="20%">{__("group")}</th>
                    <th width="40%">{__("categories")}</th>
                    <th width="5%">&nbsp;</th>
                    <th width="10%" class="right">{__("status")}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$features item="p_feature"}
                    {if $p_feature.feature_type == "ProductFeatures::EXTENDED"|enum}
                        {$show_in_popup = false}
                    {else}
                        {$show_in_popup = true}
                    {/if}

                    {$non_editable = !$p_feature|fn_allow_save_object:"product_features"}

                    {if $p_feature.parent_id && isset($group_features[$p_feature.parent_id])}
                        {$group_feature = $group_features[$p_feature.parent_id]}
                    {else}
                        {$group_feature = false}
                    {/if}
                    {$href_edit="product_features.update?feature_id=`$p_feature.feature_id`{if $show_in_popup}&return_url=`$r_url`{/if}"}
                    {$href_delete="product_features.delete?feature_id=`$p_feature.feature_id`&return_url=$r_url"}

                    <tr class="cm-row-item cm-row-status-{$p_feature.status|lower}" data-ct-product_features="{$p_feature.feature_id}">
                        <td class="left">
                            <input type="checkbox" name="feature_ids[]" value="{$p_feature.feature_id}" class="checkbox cm-item cm-item-status-{$p_feature.status|lower}" />
                        </td>
                        <td>
                            <div class="object-group-link-wrap">
                                {if !$non_editable}
                                    <a {if !$show_in_popup}href="{$href_edit|fn_url}"{/if} class="row-status cm-external-click {if $non_editable} no-underline{/if}"{if !$non_editable} data-ca-external-click-id="opener_group{$p_feature.feature_id}"{/if}>{$p_feature.description}</a>
                                {else}
                                    <span class="unedited-element block">{$p_feature.description|default:__("view")}</span>
                                {/if}
                                {include file="views/companies/components/company_name.tpl" object=$p_feature}
                            </div>
                        </td>
                        <td>
                            {if $group_feature}
                                <div class="object-group-link-wrap cm-row-status-{$group_feature.status|lower}">
                                    {if $group_feature.status != "A"}
                                        {$group_link_class = "row-status"}
                                    {else}
                                        {$group_link_class = ""}
                                    {/if}
                                    {if !$non_editable}
                                        {include file="common/popupbox.tpl" link_class="{$group_link_class}" id="group`$group_feature.feature_id`" link_text=$group_feature.description text="{__("editing_group")}: `$group_feature.description`" act="edit" href="product_features.update?feature_id=`$group_feature.feature_id`&return_url=`$r_url`" no_icon_link=true}
                                    {else}
                                        <span class="unedited-element block">{$group_feature.description|default:__("view")}</span>
                                    {/if}
                                </div>
                            {else}
                                -
                            {/if}
                        </td>
                        <td>
                            <div class="row-status object-group-details">
                            {if $group_feature}
                                {$group_feature.feature_description nofilter}
                            {else}
                                {$p_feature.feature_description nofilter}
                            {/if}
                            </div>
                        </td>
                        <td class="nowrap">
                            <div class="hidden-tools">
                                {capture name="tools_list"}
                                    {if !$non_editable}
                                        {if !$show_in_popup}
                                            <li>{btn type="list" text=__("edit") href=$href_edit}</li>
                                        {else}
                                            <li>{include file="common/popupbox.tpl" id="group`$p_feature.feature_id`" text="{__("editing_product_feature")}: `$p_feature.description`" act="edit" href=$href_edit no_icon_link=true}</li>
                                        {/if}
                                        <li>{btn type="text" text=__("delete") href=$href_delete class="cm-confirm cm-tooltip cm-ajax cm-ajax-force cm-ajax-full-render cm-delete-row" data=["data-ca-target-id" => "pagination_contents"] method="POST"}</li>
                                    {else}
                                        <li>{include file="common/popupbox.tpl" id="group`$p_feature.feature_id`" text="{__("view_product_features")}: `$p_feature.description`" act="edit" link_text=__("view") href=$href_edit no_icon_link=true}</li>
                                    {/if}
                                {/capture}
                                {dropdown content=$smarty.capture.tools_list}
                            </div>
                        </td>
                        <td class="right nowrap">
                            {include file="common/select_popup.tpl" popup_additional_class="dropleft" id=$p_feature.feature_id status=$p_feature.status hidden=true object_id_name="feature_id" table="product_features" update_controller="product_features"}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}
    <!--update_features_list--></div>
    </form>

    {include file="common/pagination.tpl"}
    {capture name="adv_buttons"}
        {capture name="add_new_picker_2"}
            {include file="views/product_features/update.tpl" feature=[] in_popup=true return_url=$config.current_url}
        {/capture}
        {include file="common/popupbox.tpl" id="add_new_feature" text=__("new_feature") title=__("new_feature") content=$smarty.capture.add_new_picker_2 act="general" icon="icon-plus"}
    {/capture}

    {capture name="sidebar"}
        {include file="common/saved_search.tpl" dispatch="product_features.manage" view_type="product_features"}
        {include file="views/product_features/components/product_features_search_form.tpl" dispatch="product_features.manage"}
    {/capture}

    {capture name="buttons"}
        {capture name="tools_list"}
            {if $features}
                <li>{btn type="delete_selected" dispatch="dispatch[product_features.m_delete]" form="manage_product_features_form"}</li>
            {/if}
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("features") content=$smarty.capture.mainbox select_languages=true buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar}
