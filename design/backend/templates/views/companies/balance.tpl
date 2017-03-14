{capture name="mainbox"}

    {capture name="tabsbox"}
    {/capture}

    {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section|default:"transactions" group_name="vendor_payouts"}

    {if $runtime.company_id}
        {assign var="hide_controls" value=true}
    {/if}

    {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {assign var="c_icon" value="<i class=\"icon-`$search.sort_order_rev`\"></i>"}
    {assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}

    <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="manage_payouts_form">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true}

        <input type="hidden" name="redirect_url" value="{$c_url}"/>
        {if $payouts}
            <table width="100%" class="table table-middle" id="payouts_list">
                <thead>
                <tr>
                    <th class="left">{include file="common/check_items.tpl"}</th>
                    <th width="5%">
                        <span id="on_st"
                              alt="{__("expand_collapse_list")}"
                              title="{__("expand_collapse_list")}"
                              class=" hand cm-combinations-visitors">
                            <span class="icon-caret-right"></span>
                        </span>
                        <span id="off_st"
                              alt="{__("expand_collapse_list")}"
                              title="{__("expand_collapse_list")}"
                              class="hand hidden cm-combinations-visitors">
                            <span class="icon-caret-down"></span>
                        </span>
                    </th>
                    <th width="5%">{__("status")}</th>
                    <th>
                        <a class="cm-ajax"
                           href="{"`$c_url`&sort_by=sort_date&sort_order=`$search.sort_order_rev`"|fn_url}"
                           data-ca-target-id="pagination_contents">
                            {__("date")}{if $search.sort_by == "sort_date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}
                        </a>
                    </th>
                    <th>{__("vendor_payouts.type")}</th>
                    {if !$hide_controls}
                        <th>{__("vendor")}</th>
                    {/if}
                    <th class="center" width="5%">&nbsp;</th>
                    {hook name="companies:balance_list_th"}{/hook}
                    <th width="15%" class="right">{__("vendor_payouts.transaction_value")}</th>
                </tr>
                </thead>
                {foreach name="payouts" from=$payouts item=payout}
                    <tr class="payout payout-{$payout.payout_type|lower}">
                        <td class="left">
                            <input type="checkbox" name="payout_ids[]" value="{$payout.payout_id}" class="cm-item"/>
                        </td>
                        <td class="left approval-status-{$payout.approval_status|lower}">
                            <span name="plus_minus"
                                  id="on_payout_note_{$smarty.foreach.payouts.iteration}"
                                  alt="{__("expand_collapse_list")}"
                                  title="{__("expand_collapse_list")}"
                                  class="hand cm-combination-visitors">
                                <span class="icon-caret-right"></span>
                            </span>
                            <span name="minus_plus"
                                  id="off_payout_note_{$smarty.foreach.payouts.iteration}"
                                  alt="{__("expand_collapse_list")}"
                                  title="{__("expand_collapse_list")}"
                                  class="hand hidden cm-combination-visitors">
                                <span class="icon-caret-down"></span>
                            </span>
                        </td>
                        <td class="nowrap">
                            {if $payout.payout_type == "VendorPayoutTypes::PAYOUT"|enum
                            || $payout.payout_type == "VendorPayoutTypes::WITHDRAWAL"|enum
                            }
                                {include
                                    file="common/select_popup.tpl"
                                    id=$payout.payout_id
                                    status=$payout.approval_status
                                    items_status=$approval_statuses
                                    notify_vendor=true
                                    update_controller="companies.payouts"
                                    st_return_url=$config.current_url
                                    st_result_ids="balance_total,payouts_list"
                                    hide_for_vendor=$hide_controls
                                }
                            {/if}
                        </td>
                        <td>
                            {$payout.payout_date|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                        </td>
                        <td>
                            {hook name="companies:payout_type_description"}
                            {$payout.payout_type_description|sanitize_html nofilter}
                            {/hook}
                        </td>
                        {if !$runtime.company_id}
                            <td>
                                {if $payout.company_id}
                                    {$payout.company|default:__("deleted")}
                                {else}
                                    {$settings.Company.company_name}
                                {/if}
                            </td>
                        {/if}
                        {hook name="companies:balance_list_tr"}{/hook}
                        <td class="center nowrap">
                            {if !$hide_controls}
                                <div class="hidden-tools">
                                    {capture name="tools_list"}
                                        <li>{btn type="list" class="cm-confirm" text=__("delete") href="companies.payout_delete?payout_id=`$payout.payout_id`&redirect_url={$c_url|rawurlencode}" method="POST"}</li>
                                    {/capture}
                                    {dropdown content=$smarty.capture.tools_list}
                                </div>
                            {/if}
                        </td>
                        <td class="right">
                            {* total balance change *}
                            {hook name="companies:payout_amount"}
                                {include file="common/price.tpl" value=$payout.display_amount}
                            {/hook}
                        </td>
                    </tr>
                    <tr id="payout_note_{$smarty.foreach.payouts.iteration}"
                        class="row-more {if $hide_extra_button != "Y"}hidden{/if}">
                        <td colspan="8" class="row-more-body top row-gray">
                            <div class="control-group">
                                <label class="control-label"
                                       for="payout_comments_{$payout.payout_id}">
                                    {__("comment")}
                                </label>
                                <div class="controls">
                                    {if $runtime.company_id}
                                        <p>{if $payout.comments}{$payout.comments}{else}-{/if}</p>
                                    {else}
                                        <textarea class="span6"
                                                  rows="4"
                                                  cols="25"
                                                  name="payout_comments[{$payout.payout_id}]"
                                                  id="payout_comments_{$payout.payout_id}">{strip}
                                            {$payout.comments}
                                        {/strip}</textarea>
                                    {/if}
                                </div>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            <!--payouts_list--></table>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        <div class="clearfix">
            {include file="common/pagination.tpl"}
        </div>

        {if $payouts && $totals}
            {include file="views/companies/components/balance_info.tpl"}
        {/if}
    </form>
    {capture name="buttons"}
        {if !$hide_controls && $payouts}
            {capture name="tools_list"}
                <li>{btn type="delete_selected" dispatch="dispatch[companies.m_delete_payouts]" form="manage_payouts_form"}</li>
            {/capture}
            {dropdown content=$smarty.capture.tools_list}

            {include file="buttons/button.tpl"
                     but_text=__("save")
                     but_name="dispatch[companies.update_payout_comments]"
                     but_role="submit-link"
                     but_target_form="manage_payouts_form"
            }
        {/if}
    {/capture}

    {capture name="adv_buttons"}
        {capture name="add_new_picker"}
            {include file="views/companies/components/balance_new_payment.tpl" c_url=$c_url}
        {/capture}
        {if $runtime.company_id}
            {$popup_title = __("new_withdrawal")}
            {$btn_title = __("add_withdrawal")}
        {else}
            {$popup_title = __("new_payout")}
            {$btn_title = __("add_payout")}
        {/if}
        {include file="common/popupbox.tpl"
                 id="add_payment"
                 text=$popup_title
                 content=$smarty.capture.add_new_picker
                 title=$btn_title
                 act="general"
                 icon="icon-plus"
        }
    {/capture}

    {capture name="sidebar"}
        {include file="common/saved_search.tpl" dispatch="companies.balance" view_type="balance"}
        {include file="views/companies/components/balance_search_form.tpl" dispatch="companies.balance"}
    {/capture}

{/capture}
{capture name="mainbox_title"}
    {__("vendor_accounting")}
    {if $current_balance}
        {capture name="balance"}
            {include file="common/price.tpl" value=$current_balance}
        {/capture}
        <span class="f-middle">{__("vendor_payouts.current_balance", ["[balance]" => $smarty.capture.balance])}</span>
    {/if}
{/capture}
{include file="common/mainbox.tpl"
         title=$smarty.capture.mainbox_title
         content=$smarty.capture.mainbox
         buttons=$smarty.capture.buttons
         adv_buttons=$smarty.capture.adv_buttons
         sidebar=$smarty.capture.sidebar
}