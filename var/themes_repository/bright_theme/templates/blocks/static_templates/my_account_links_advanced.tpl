<ul id="account_info_links_{$block.snapping_id}" class="ty-account-info__links">
{if $auth.user_id}
    <li><a href="{"profiles.update"|fn_url}">{__("profile_details")}</a></li>
{else}
    <li><a href="{"auth.login_form"|fn_url}">{__("sign_in")}</a></li>
    <li><a href="{"profiles.add"|fn_url}">{__("create_account")}</a></li>
{/if}
    <li><a href="{"orders.search"|fn_url}">{__("orders")}</a></li>
    <li><a href="{"wishlist.view"|fn_url}">{__("wishlist")}</a></li>
    <li><a href="{"product_features.compare"|fn_url}">{__("comparison_list")}</a></li>
<!--account_info_links_{$block.snapping_id}--></ul>