<ul id="account_info_links_{$block.snapping_id}">
{if $auth.user_id}
    <li class="ty-footer-menu__item"><a href="{"orders.search"|fn_url}">{__("orders")}</a></li>
    <li class="ty-footer-menu__item"><a href="{"profiles.update"|fn_url}">{__("profile_details")}</a></li>
{else}
    <li class="ty-footer-menu__item"><a href="{"auth.login_form"|fn_url}" rel="nofollow">{__("sign_in")}</a></li>
    <li class="ty-footer-menu__item"><a href="{"profiles.add"|fn_url}" rel="nofollow">{__("create_account")}</a></li>
{/if}
<!--account_info_links_{$block.snapping_id}--></ul>