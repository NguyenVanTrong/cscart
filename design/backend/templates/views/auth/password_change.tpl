<div class="modal signin-modal">
    <form action="{""|fn_url}" method="post" name="main_login_form" class=" cm-skip-check-items">
        <input type="hidden" name="return_url" value="{$smarty.request.return_url|fn_url}">

        <div class="modal-header">
            <h4>{__("administration_panel")}</h4>
        </div>
        <div class="modal-body">
            <p>{__("error_password_expired")}</p>
            <label>{__("email")}:</label>
            <div id="email" class="input-text">{$user_data.email}</div>
            <label for="password1" class="cm-required">{__("password")}:</label>
            <input type="password" id="password1" name="user_data[password1]" class="input-text cm-autocomplete-off" size="20" maxlength="32" value="            ">

            <label for="password2" class="cm-required">{__("confirm_password")}:</label>
            <input type="password" id="password2" name="user_data[password2]" class="input-text cm-autocomplete-off" size="20" maxlength="32" value="            ">
        </div>
        <div class="modal-footer">
            {include file="buttons/button.tpl" but_text=__("save") but_name="dispatch[auth.password_change]" but_role="button_main"}
            <a href="{"auth.logout"|fn_url}" class="pull-right">{__("sign_out")}</a>
        </div>
    </form>
</div>