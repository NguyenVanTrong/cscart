<div class="ebay-end-summary">
    
    {if !empty($end_result.errors)}
        <h4 class="text-error">{__('errors')}</h4>
        <table width="100%" class="table table-no-hover">
            {foreach from=$end_result.errors key=code item=error name="errors"}
                <tr {if $smarty.foreach.errors.first} class="no-border"{/if}>
                    <td class="text-error"><strong>{$code}</strong> - {$error}</td>
                </tr>
            {/foreach}
        </table>
        {if (!empty($end_result.count_external_error))}
            <a href="{"ebay.product_logs"|fn_url}" class="btn">{__('ebay_show_logs')}</a>
        {/if}
    {/if}

    <table width="100%" class="table table-no-hover">
        <tr class="no-border">
            <td width="60%"><strong>{__('ebay_count_product_successfully_ended')}</strong></td>
            <td align="right">{$end_result.count_success}</td>
        </tr>
        <tr>
            <td width="60%"><strong>{__('ebay_count_product_fail_ended')}</strong></td>
            <td align="right">{$end_result.count_fail}</td>
        </tr>
        <tr>
            <td width="60%"><strong>{__('ebay_count_product_skip')}</strong></td>
            <td align="right">{$end_result.count_skip}</td>
        </tr>
    </table>
    <div>
        <a class="btn cm-notification-close pull-right">{__("close")}</a>
    </div>
</div>