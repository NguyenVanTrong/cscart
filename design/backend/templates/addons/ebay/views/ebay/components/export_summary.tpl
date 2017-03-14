<div class="ebay-export-summary">
    {if !empty($export_result.errors)}
        <div class="alert alert-error">
            <p>{__("ebay_export_failed_msg")}</p>
            {if (!empty($export_result.count_external_error))}
                <a href="{"ebay.product_logs"|fn_url}" class="btn">{__('ebay_show_logs')}</a>
            {/if}
        </div>
        <h4 class="text-error">{__('errors')}</h4>
        <table width="100%" class="table table-no-hover">
            {foreach from=$export_result.errors key=code item=error name="errors"}
                <tr {if $smarty.foreach.errors.first} class="no-border"{/if}>
                    <td class="text-error"><strong>{$code}</strong> - {$error}</td>
                </tr>
            {/foreach}
        </table>
    {else}
        <div class="alert alert-success">
            <p>{__("ebay_export_success_msg")}</p>
        </div>
    {/if}
    <table width="100%" class="table table-no-hover">
        <tr class="no-border">
            <td width="60%"><strong>{__('ebay_count_product_successfully_exported')}</strong></td>
            <td align="right">{$export_result.count_success}</td>
        </tr>
        <tr>
            <td width="60%"><strong>{__('ebay_count_product_fail_exported')}</strong></td>
            <td align="right">{$export_result.count_fail}</td>
        </tr>
        <tr>
            <td width="60%"><strong>{__('ebay_count_product_skip')}</strong></td>
            <td align="right">{$export_result.count_skip}</td>
        </tr>
    </table>
    <div>
        <a class="btn cm-notification-close pull-right">{__("close")}</a>
    </div>
</div>