{capture name="mainbox"}
    <form action="{""|fn_url}" method="post" name="edit_order_invoice_form" class="form-horizontal form-edit">
        <input type="hidden" value="{$order_info.order_id}" name="order_id" />
        <div class="control-group">
            <label for="elm_subject" class="cm-required control-label">{__("subject")}:</label>
            <div class="controls">
                <input id="elm_subject" type="text" name="invoice[subject]" value="{__("email_order_invoice_subject", ["[company_name]" => $company_data.company_name, "[order_id]" => $order_info.order_id])}" class="span9">
            </div>
        </div>

        <div class="control-group">
            <label for="elm_email" class="cm-required cm-email control-label">{__("email")}:</label>
            <div class="controls">
                <input id="elm_email" type="text" name="invoice[email]" value="{$order_info.email}" class="span9">
            </div>
        </div>

        <div class="control-group">
            <label for="elm_invoice" class="cm-required control-label">{__("invoice")}:</label>
            <div class="controls">
                <textarea id="elm_invoice" name="invoice[body]" cols="55" rows="14" class="span9 cm-wysiwyg">{$invoice}</textarea>
            </div>
        </div>

        <div class="control-group">
            <label for="elm_attach_invoice" class="control-label">{__("email_template.params.attach_order_document")}:</label>
            <div class="controls">
                <input type="hidden" name="invoice[attach]" value="N" />
                <input type="checkbox" id="elm_attach_invoice" name="invoice[attach]" value="Y" />
            </div>
        </div>
    </form>

{/capture}

{capture name="buttons"}
    {include file="buttons/button.tpl" but_text=__("send") but_name="dispatch[orders.modify_invoice]" but_target_form="edit_order_invoice_form" but_role="submit-link"}
{/capture}

{include file="common/mainbox.tpl"
    title=__("editing_order_invoice", ["[order_id]" => $order_info.order_id])
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
}
