{hook name="vendors:apply_page"}
<div class="ty-company-fields">
    {include file="views/profiles/components/profiles_scripts.tpl"}

    <h1 class="ty-mainbox-title">{__("apply_for_vendor_account")}</h1>

    <div id="apply_for_vendor_account" >

        <form action="{"companies.apply_for_vendor"|fn_url}" method="post" name="apply_for_vendor_form">
            {hook name="vendors:apply_fields"}
            
            <div class="ty-control-group">
                <label for="company_description_company" class="ty-control-group__title cm-required">{__("company")}</label>
                <input type="text" name="company_data[company]" id="company_description_company" size="32" value="{$company_data.company}" class="ty-input-text cm-focus" />
            </div>

            {hook name="vendors:apply_description"}
            <div class="ty-control-group">
                <label class="ty-control-group__title" for="company_description">{__("description")}</label>
                <textarea id="company_description" name="company_data[company_description]" cols="55" rows="5" class="ty-input-textarea-long">{$company_data.company_description}</textarea>
            </div>
            {/hook}

            {if $languages|count > 1}
            <div class="ty-control-group">
                <label class="ty-control-group__title" for="company_language">{__("language")}</label>
                <select name="company_data[lang_code]" id="company_language">
                    {foreach from=$languages item="language" key="lang_code"}
                        <option value="{$lang_code}" {if $lang_code == $company_data.lang_code}selected="selected"{/if}>{$language.name}</option>
                    {/foreach}
                </select>
            </div>
            {else}
            <input type="hidden" name="company_data[lang_code]" value="{$languages|key}" />
            {/if}

            {if !$auth.user_id && $settings.Vendors.create_vendor_administrator_account == "Y"}

                {literal}
                <script type="text/javascript">

                function fn_toggle_required_fields() {
                    var $ = Tygh.$;
                    var f = $('#company_admin_firstname');
                    var l = $('#company_admin_lastname');
                    var flag = ($('#company_request_account_name').val() == '');

                    f.prop('disabled', flag).toggleClass('disabled', flag);
                    l.prop('disabled', flag).toggleClass('disabled', flag);

                    $('.cm-profile-field').each(function(index) {
                        var elm = $('#' + $(this).prop('for'));
                        if (elm.children() != null) {
                            // Traverse subitems
                            $('.' + $(this).prop('for')).prop('disabled', flag).toggleClass('disabled', flag);
                        }
                        elm.prop('disabled', flag).toggleClass('disabled', flag);
                    });
                }
                </script>
                {/literal}

                {assign var="disabled_by_default" value=false}
                <div class="ty-control-group" id="company_description_admin_firstname">
                    <label for="company_admin_firstname" class="ty-control-group__title cm-required">{__("first_name")}</label>
                    <input type="text" name="company_data[admin_firstname]" id="company_admin_firstname" size="32" value="{$company_data.admin_firstname}" class="ty-input-text" />
                </div>
                <div class="ty-control-group" id="company_description_admin_lastname">
                    <label for="company_admin_lastname" class="ty-control-group__title cm-required">{__("last_name")}</label>
                    <input type="text" name="company_data[admin_lastname]" id="company_admin_lastname" size="32" value="{$company_data.admin_lastname}" class="ty-input-text" />
                </div>

            {/if}

            {if !$auth.user_id}
                {include file="views/profiles/components/profile_fields.tpl" section="C" title=__("contact_information") disabled_by_default=$disabled_by_default}
            {else}
                {include file="common/subheader.tpl" title=__("contact_information")}
            {/if}

            <div class="ty-control-group">
                <label for="company_description_email" class="ty-control-group__title cm-required cm-email cm-trim">{__("email")}</label>
                <input type="text" name="company_data[email]" id="company_description_email" size="32" value="{$company_data.email}" class="ty-input-text" />
            </div>

            <div class="ty-control-group">
                <label for="company_description_phone" class="ty-control-group__title cm-required">{__("phone")}</label>
                <input type="text" name="company_data[phone]" id="company_description_phone" size="32" value="{$company_data.phone}" class="ty-input-text" />
            </div>

            <div class="ty-control-group">
                <label class="ty-control-group__title" for="company_description_url">{__("url")}</label>
                <input type="text" name="company_data[url]" id="company_description_url" size="32" value="{$company_data.url}" class="ty-input-text" />
            </div>

            <div class="ty-control-group">
                <label class="ty-control-group__title" for="company_description_fax">{__("fax")}</label>
                <input type="text" name="company_data[fax]" id="company_description_fax" size="32" value="{$company_data.fax}" class="ty-input-text" />
            </div>


            {if !$auth.user_id}
                {include file="views/profiles/components/profile_fields.tpl" section="B" title=__("shipping_address") shipping_flag=false disabled_by_default=$disabled_by_default}
            {else}
                {include file="common/subheader.tpl" title=__("shipping_address")}
            {/if}

            <div class="ty-control-group">
                <label class="ty-control-group__title cm-required" for="company_address_address">{__("address")}</label>
                <input type="text" name="company_data[address]" id="company_address_address" size="32" value="{$company_data.address}" class="ty-input-text" />
            </div>

            <div class="ty-control-group">
                <label class="ty-control-group__title cm-required" for="company_address_city">{__("city")}</label>
                <input type="text" name="company_data[city]" id="company_address_city" size="32" value="{$company_data.city}" class="ty-input-text" />
            </div>

            <div class="ty-control-group  shipping-country">
                <label for="company_address_country" class="ty-control-group__title cm-required">{__("country")}</label>
                {assign var="_country" value=$company_data.country|default:$settings.General.default_country}
                <select class="cm-country cm-location-shipping" id="company_address_country" name="company_data[country]">
                    <option value="">- {__("select_country")} -</option>
                    {foreach from=$countries item="country" key="code"}
                    <option {if $_country == $code}selected="selected"{/if} value="{$code}">{$country}</option>
                    {/foreach}
                </select>
            </div>

            {$_country = $company_data.country|default:$settings.General.default_country}
            {$_state = $company_data.state|default:$settings.General.default_state}

            <div class="ty-control-group shipping-state">
                <label for="company_address_state" class="ty-control-group__title cm-required">{__("state")}</label>
                <select id="company_address_state" name="company_data[state]" class="cm-state cm-location-shipping {if !$states.$_country}hidden{/if}">
                    <option value="">- {__("select_state")} -</option>
                    {if $states && $states.$_country}
                        {foreach from=$states.$_country item=state}
                            <option {if $_state == $state.code}selected="selected"{/if} value="{$state.code}">{$state.state}</option>
                        {/foreach}
                    {/if}
                </select>
                <input type="text" id="company_address_state_d" name="company_data[state]" size="32" maxlength="64" value="{$_state}" {if $states.$_country}disabled="disabled"{/if} class="cm-state cm-location-shipping ty-input-text {if $states.$_country}hidden{/if} cm-skip-avail-switch" />
            </div>

            <div class="ty-control-group shipping-zip-code">
                <label for="company_address_zipcode" class="ty-control-group__title cm-required cm-zipcode cm-location-shipping">{__("zip_postal_code")}</label>
                <input type="text" name="company_data[zipcode]" id="company_address_zipcode" size="32" value="{$company_data.zipcode}" class="ty-input-text" />
            </div>
            
            {if $settings.Vendors.need_agree_with_terms_n_conditions == "Y"}
                <div class="ty-control-group ty-company__terms">
                    <div class="cm-field-container">
                        {strip}
                        <label for="id_accept_terms{$suffix}" class="cm-check-agreement">
                            <input type="checkbox" id="id_accept_terms{$suffix}" name="accept_terms" value="Y" class="cm-agreement checkbox" {if $iframe_mode}onclick="fn_check_agreements('{$suffix}');"{/if} />
                            {capture name="terms_link"}
                                <a id="sw_terms_and_conditions_{$suffix}" class="cm-combination ty-dashed-link">
                                    {__("vendor_terms_n_conditions_name")}
                                </a>
                            {/capture}
                            {__("vendor_terms_n_conditions", ["[terms_href]" => $smarty.capture.terms_link])}
                        </label>
                        {/strip}

                        <div class="hidden" id="terms_and_conditions_{$suffix}">
                            {__("vendor_terms_n_conditions_content") nofilter}
                        </div>
                    </div>
                    <script type="text/javascript">
                        (function(_, $) {
                            $.ceFormValidator('registerValidator', {
                                class_name: 'cm-check-agreement',
                                message: '{__("vendor_terms_n_conditions_alert")|escape:javascript}',
                                func: function(id) {
                                    return $('#' + id).prop('checked');
                                }
                            });     
                        }(Tygh, Tygh.$));
                    </script>
                </div>
            {/if}
            
            {/hook}

            {include file="common/image_verification.tpl" option="apply_for_vendor_account" align="left"}

            <div class="buttons-container">
                {include file="buttons/button.tpl" but_text=__("submit") but_name="dispatch[companies.apply_for_vendor]" but_id="but_apply_for_vendor" but_meta="ty-btn__primary"}
            </div>
        </form>
    </div>
</div>
{/hook}