{** block-description:tmpl_newsletters_subscription_advanced **}
{if $addons.newsletters}
<div class="ty-footer-form-block ty-footer-newsletters-block no-help">
    <form action="{""|fn_url}" method="post" name="subscribe_form">
        <input type="hidden" name="redirect_url" value="{$config.current_url}" />
        <input type="hidden" name="newsletter_format" value="2" />
        <h3 class="ty-footer-form-block__title">{__("stay_connected")}</h3>
        <div class="ty-footer-form-block__form ty-control-group with-side">
            <h3 class="ty-uppercase ty-social-link__title"><i class="ty-icon-moon-mail"></i>{__("exclusive_promotions")}<span class="ty-block">{__("exclusive_promotions_content")}</span></h3>
        </div>
        <div class="ty-footer-form-block__form ty-control-group">
            <div class="ty-footer-form-block__input">
            <label class="cm-required cm-email hidden" for="subscr_email{$block.block_id}">{__("email")}</label>
                <input type="text" name="subscribe_email" id="subscr_email{$block.block_id}" size="20" placeholder="{__("email")}" class="cm-hint ty-input-text-medium ty-valign-top" />
            </div>
            <div class="ty-footer-form-block__button">
            {include file="buttons/button.tpl" but_role="submit" but_name="dispatch[newsletters.add_subscriber]" but_text=__("subscribe") but_meta="ty-btn__subscribe"}
            </div>
        </div>
    </form>
</div>
{/if}