{if $option|fn_needs_image_verification}
    {assign var="id" value="recaptcha_"|uniqid}
    <div class="captcha ty-control-group">
        <label for="{$id}" class="cm-required cm-recaptcha ty-captcha__label">{__("image_verification_label")}</label>
        <div id="{$id}" class="cm-recaptcha"></div>
    </div>
{/if}