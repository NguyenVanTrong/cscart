{*
    This template renders the add-on licensing form, which is intended to be shown in a popup.

    Form contains:
    * "Marketplace License Key" field
*}

<form action="{""|fn_url}"
      method="post"
      name="licensing_addon_{$addon_data.addon}_form"
      class="form-edit form-horizontal" enctype="multipart/form-data">

    <input type="hidden" name="addon" value="{$addon_data.addon}"/>
    <input type="hidden" name="redirect_url" value="{$redirect_url}">

    <div class="control-group">

        <label class="control-label">{__("license_number")}{include file="common/tooltip.tpl" tooltip={__("addon_license_key_tooltip")}}
            :</label>

        <div class="controls">
            <input type="text" name="marketplace_license_key"
                   value="{$addon_data.marketplace_license_key}"
                   size="30"/>
        </div>
    </div>

    <div class="buttons-container cm-toggle-button">
        {include file="buttons/save_cancel.tpl" but_name="dispatch[addons.licensing]" cancel_action="close" save=true}
    </div>
</form>