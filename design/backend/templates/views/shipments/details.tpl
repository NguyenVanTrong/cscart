{capture name="mainbox"}

{capture name="tabsbox"}
<div id="content_general">

<form name="update_shipment_form" action="{""|fn_url}" method="post">
<input type="hidden" name="shipment_id" value="{$shipment.shipment_id}" />
    {* Customer info *}
    {include file="views/profiles/components/profiles_info.tpl" user_data=$order_info location="I"}
    {* /Customer info *}

    {hook name="shipments:additional_info"}
    {/hook}

    <table width="100%" class="table table-middle">
    <thead>
        <tr>
            <th>{__("product")}</th>
            <th width="5%">{__("quantity")}</th>
        </tr>
    </thead>
    {foreach from=$order_info.products item="oi" key="key"}
    {if $oi.amount > 0}
    <tr>
        <td>
            {if !$oi.deleted_product}<a href="{"products.update?product_id=`$oi.product_id`"|fn_url}">{/if}{$oi.product nofilter}{if !$oi.deleted_product}</a>{/if}
            {hook name="shipments:product_info"}
            {if $oi.product_code}</p>{__("sku")}:&nbsp;{$oi.product_code}</p>{/if}
            {/hook}
            {if $oi.product_options}<div class="options-info">{include file="common/options_info.tpl" product_options=$oi.product_options}</div>{/if}
        </td>
        <td class="center">
            &nbsp;{$oi.amount}<br />
        </td>
    </tr>
    {/if}
    {/foreach}
    </table>

    <div class="order-notes statistic">
        <div class="row-fluid">
            <h3><label for="notes">{__("comments")}</label></h3>
            <textarea class="input-xxlarge" cols="40" rows="5" name="shipment_data[comments]">{$shipment.comments}</textarea>
        </div>
        <div class="row-fluid">
            <h3><label for="elm_date_holder">{__("shipment_date")}</label></h3>
            {include file="common/calendar.tpl" date_id="elm_date_holder" date_name="shipment_data[date][date]" date_val=$shipment.shipment_timestamp start_year=$settings.Company.company_start_year show_time=true time_name="shipment_data[date][time]"}
        </div>
    </div>
</form>
<!--content_general--></div>
{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section track=true}

{/capture}

{capture name="sidebar"}
    <div class="sidebar-row">
        <h6>{__("shipment_info")}</h6>
        <p>{__("shipment")} #{$shipment.shipment_id}
        {__("on")} {$shipment.shipment_timestamp|date_format:"`$settings.Appearance.date_format`"} <br />
        {__("by")} {$shipment.shipping} <br />{if $shipment.tracking_number} ({$shipment.tracking_number}){/if}{if $shipment.carrier} ({$shipment.carrier_info.name nofilter}){/if}</p>

        <h6>{__("status")}</h6>

        {include file="common/select_popup.tpl" id=$shipment.shipment_id status=$shipment.status items_status=$shipment_statuses table="shipments" object_id_name="shipment_id" popup_additional_class="dropleft"}

        {hook name="shipments:customer_shot_info"}
        {/hook}
    </div>
{/capture}

{capture name="buttons"}

    {include file="buttons/save_changes.tpl" but_role="submit-link" but_target_form="update_shipment_form" but_name="dispatch[shipments.update]" save=true}

    {capture name="tools_list"}
        {hook name="shipments:details_tools"}
            <li>{btn type="list" text="{__("order")} #`$order_info.order_id`" href="orders.details?order_id=`$order_info.order_id`"}</li>
            <li>{btn type="list" text=__("print_packing_slip") href="shipments.packing_slip?shipment_ids[]=`$shipment.shipment_id`" class="cm-new-window"}</li>
            <li>{btn type="list" text=__("print_pdf_packing_slip") href="shipments.packing_slip?shipment_ids[]=`$shipment.shipment_id`&format=pdf" class="cm-new-window"}</li>
            <li class="divider"></li>
            <li>{btn type="list" text=__("delete") class="cm-confirm" href="shipments.delete?shipment_ids[]=`$shipment.shipment_id`" method="POST"}</li>
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}

{include file="common/mainbox.tpl" title=__("shipment_details") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar}
