<div id="content_ebay" class="cm-hide-save-button hidden">
    <div class="control-group">
        <label for="elm_category_name" class="control-label">{__("ebay_category_preferences") nofilter} {include file="common/tooltip.tpl" tooltip={__("ebay_category_preferences_tooltip")}}:</label>
        <div class="controls">
            <select id="ebay_product_category_matching">
                <option value="template">{__("ebay_template_category_product")}</option>
                <option value="manually"{if $category_data.ebay_category_id > 0} selected="selected"{/if}>{__("ebay_set_manualy")}</option>
            </select>
        </div>
    </div>

    <div class="control-group{if empty($category_data.ebay_category_id)} hidden{/if}" id="ebay_set_manualy">
        <label for="elm_category_name" class="control-label">{__("ebay_associated_category")}:</label>
        <div class="controls" >
            {include file="addons/ebay/pickers/categories/picker.tpl" data_id="ebay_category" company_id=$category_data.company_id input_name="category_data[ebay_category]" item_ids=$category_data.ebay_category_id site_id=$category_data.ebay_site_id hide_link=true hide_delete_button=false}
        </div>
    </div>
    <script type="text/javascript">
        (function(_, $) {
            $(document).ready(function() {
                $('#ebay_product_category_matching').on('change', function() {
                    var div_set_manual = $('#ebay_set_manualy');
                    div_set_manual.toggle();

                    if ($(this).val() == 'template') {
                        div_set_manual.find('.cm-picker-value').val('');
                        div_set_manual.find('.cm-picker-value-description').val('');
                    }
                });
            });
        }(Tygh, Tygh.$));
    </script>
</div>