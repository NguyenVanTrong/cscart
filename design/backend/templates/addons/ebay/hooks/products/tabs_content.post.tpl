<div id="content_ebay" class="cm-hide-save-button hidden">
    {if $ebay_templates}
        <div id="acc_ebay" class="collapse in">
            <div class="control-group">
                <label class="control-label" for="elm_ebay_template_id">{__("ebay_template")}:</label>
                <div class="controls">
                    <select class="span3" name="product_data[ebay_template_id]" id="elm_ebay_template_id">
                        <option value="0">{__('select')}</option>
                        {foreach from=$ebay_templates item="template"}
                            <option value="{$template.template_id}" {if $product_data.ebay_template_id == $template.template_id || (empty($product_data.ebay_template_id) && $template.use_as_default == 'Y')}selected="selected"{/if}>{$template.name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="control-group" id="override_price" >
                <label for="elm_override_price" class="control-label">{__("override_price")}:</label>
                <div class="controls">
                    <input type="hidden" value="N" name="product_data[ebay_override_price]"/>
                    <input type="checkbox" onclick="override_price();" id="elm_override_price" name="product_data[ebay_override_price]" class="cm-toggle-checkbox cm-no-hide-input" {if $product_data.ebay_override_price == 'Y'} checked="checked"{/if} value="Y" />
                </div>
            </div>
            <div class="control-group">
                <label id="elm_ebay_price_title" class="control-label{if $product_data.ebay_override_price == 'Y'} cm-required{/if}" for="elm_ebay_price">{__("ebay_price")} ({$currencies.$primary_currency.symbol nofilter}) :</label>
                <div class="controls">
                    <input type="text" name="product_data[ebay_price]" id="elm_ebay_price" size="10" {if !empty($product_data.ebay_price)} value="{$product_data.ebay_price|fn_format_price:$primary_currency:null:false}" {else} value="{$product_data.price|fn_format_price:$primary_currency:null:false}"  {/if} class="input-long" {if $product_data.ebay_override_price != 'Y'} disabled="disabled"{/if} />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="elm_package_type">{__("ebay_package_type")}{include file="common/tooltip.tpl" tooltip={__("package_type_tooltip")}}:</label>
                <div class="controls">
                    <select class="span3" name="product_data[package_type]" id="elm_package_type">
                        <option {if  $product_data.package_type == 'Letter'}selected="selected"{/if} value="Letter">{__('Letter')}</option>
                        <option {if  $product_data.package_type == 'LargeEnvelope'}selected="selected"{/if} value="LargeEnvelope">{__('large_envelope')}</option>
                        <option {if  $product_data.package_type == 'PackageThickEnvelope'}selected="selected"{/if} value="PackageThickEnvelope">{__('ebay_package')}</option>
                        <option {if  $product_data.package_type == 'ExtraLargePack'}selected="selected"{/if} value="ExtraLargePack">{__('large_package')}</option>
                    </select>
                </div>
            </div>
            <div class="control-group" id="override" >
                <label for="elm_override" class="control-label">{__("override")}{include file="common/tooltip.tpl" tooltip={__("override_tooltip")}}:</label>
                <div class="controls">
                    <input type="hidden" value="N" name="product_data[override]"/>
                    <input type="checkbox" onclick="override();" id="elm_override" name="product_data[override]" class="cm-toggle-checkbox cm-no-hide-input" {if $product_data.override == 'Y'} checked="checked"{/if} value="Y" />
                </div>
            </div>
            <div class="control-group">
                <label for="elm_ebay_title" class="control-label {if $product_data.override == 'Y'}cm-required{/if}" id="ebay_title_req">{__("ebay_title")}{include file="common/tooltip.tpl" tooltip={__("ebay_title_tooltip")}}:</label>
                <div class="controls">
                    <input type="text" name="product_data[ebay_title]" id="elm_ebay_title" size="55" {if !empty($product_data.ebay_title)} value="{$product_data.ebay_title}" {else} value="{$product_data.product}" {/if} class="input-large cm-no-hide-input" {if $product_data.override == 'N' || empty($product_data.override)} disabled="disabled" {/if}/>
                </div>
            </div>

            <div class="control-group cm-no-hide-input">
                <label class="control-label" for="elm_ebay_full_descr">{__("ebay_description")}{include file="common/tooltip.tpl" tooltip={__("ebay_description_tooltip")}}:</label>
                <div class="controls">
                    <div id="ebay_description_wrapper" {if $product_data.override != "Y"}class="disable-overlay-wrap wysiwyg-overlay"{/if}>
                        <textarea id="elm_ebay_full_descr" data-name="product_data[ebay_description]"{if $product_data.override == "Y"} name="product_data[ebay_description]"{/if} cols="55" rows="8" class="cm-wysiwyg input-large cm-no-hide-input">
                            {if $product_data.override == "Y" || !empty($product_data.ebay_description)}
                                {$product_data.ebay_description}
                            {else}
                                {$product_data.full_description}
                            {/if}
                        </textarea>

                        <div id="elm_ebay_full_descr_overlay" class="disable-overlay{if $product_data.override == "Y"} hidden{/if}"></div>
                    </div>
                </div>
            </div>
        </div>
    {else}
        {__("ebay_templates_not_found")}
    {/if}

    <script type="text/javascript">
        function override() {
            var $ = Tygh.$,
                $elm_ebay_full_descr = $("#elm_ebay_full_descr"),
                $ebay_description_wrapper = $("#ebay_description_wrapper"),
                $elm_ebay_full_descr_overlay = $("#elm_ebay_full_descr_overlay");

            if ($("#elm_override").is(":checked")) {
                $("#elm_ebay_title").removeAttr("disabled");
                $("#ebay_title_req").addClass("cm-required");

                $elm_ebay_full_descr.attr("name", $elm_ebay_full_descr.data("name"));
                $ebay_description_wrapper.removeClass("disable-overlay-wrap wysiwyg-overlay");
                $elm_ebay_full_descr_overlay.addClass("hidden");
            } else {
                $ebay_description_wrapper.addClass("disable-overlay-wrap wysiwyg-overlay");
                $elm_ebay_full_descr_overlay.removeClass("hidden");
                $elm_ebay_full_descr.removeAttr("name");

                $("#ebay_title_req").removeClass("cm-required");
                $("#elm_ebay_title").attr("disabled","disabled");
            }
        }

        function override_price() {
            var $ = Tygh.$;

            if ($('#elm_override_price').prop('checked')) {
                $('#elm_ebay_price').prop('disabled', false);
                $('#elm_ebay_price_title').addClass("cm-required");
            } else {
                $('#elm_ebay_price').prop('disabled', true);
                $('#elm_ebay_price_title').removeClass("cm-required");
            }
        }
    </script>
</div>