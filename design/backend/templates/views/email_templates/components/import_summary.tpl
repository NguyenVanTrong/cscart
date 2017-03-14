<div class="import-summary">
    {if !empty($import_result.errors)}
        <h4 class="text-error">{__('import_errors')}</h4>
        <table width="100%" class="table table-no-hover">
            {foreach from=$import_result.errors key=code item=error name="errors"}
                <tr {if $smarty.foreach.errors.first} class="no-border"{/if}>
                    <td class="text-error">{$error}</td>
                </tr>
            {/foreach}
        </table>
    {else}
        <div class="alert alert-success">
            <p>{__("email_template_import_success_msg")}</p>
        </div>
    {/if}
    <table width="100%" class="table table-no-hover">
        <tr class="no-border">
            <td width="60%"><strong>{__('count_email_template_successfully_imported')}</strong></td>
            <td align="right">{$import_result.count_success_templates}</td>
        </tr>
        <tr>
            <td width="60%"><strong>{__('count_email_template_fail_imported')}</strong></td>
            <td align="right">{$import_result.count_fail_templates}</td>
        </tr>
        <tr>
            <td width="60%"><strong>{__('count_snippet_successfully_imported')}</strong></td>
            <td align="right">{$import_result.count_success_snippets}</td>
        </tr>
        <tr>
            <td width="60%"><strong>{__('count_snippet_fail_imported')}</strong></td>
            <td align="right">{$import_result.count_fail_snippets}</td>
        </tr>
    </table>
    <div>
        <a class="btn cm-notification-close pull-right">{__("close")}</a>
    </div>
</div>