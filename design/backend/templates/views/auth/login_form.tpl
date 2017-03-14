<div class="modal signin-modal">
    <form action="{""|fn_url}" method="post" name="main_login_form" class=" cm-skip-check-items cm-check-changes">
        <input type="hidden" name="return_url" value="{$smarty.request.return_url|fn_url:"A":"rel"|fn_query_remove:"return_url"}">
        <div class="modal-header">
            <h4>{__("administration_panel")}</h4>
        </div>
        <div class="modal-body">
            <div class="control-group">
                <label for="username" class="cm-trim cm-required cm-email">{__("email")}:</label>
                <input id="username" type="text" name="user_login" size="20" value="{if $stored_user_login}{$stored_user_login}{else}{$config.demo_username}{/if}" class="cm-focus" tabindex="1">
            </div>
            <div class="control-group">
                <label for="password" class="cm-required">{__("password")}:</label>
                <input type="password" id="password" name="password" size="20" value="{$config.demo_password}" tabindex="2" maxlength="32">
            </div>
        </div>
        <div class="modal-footer">
            {include file="buttons/sign_in.tpl" but_name="dispatch[auth.login]" but_role="button_main" tabindex="3"}
            <a href="{"auth.recover_password"|fn_url}" class="pull-right">{__("forgot_password_question")}</a>
        </div>
    </form>
</div>