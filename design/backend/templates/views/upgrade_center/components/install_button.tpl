<div class="upgrade-center_install" id="install_button_{$id}">
    {if $show_pre_upgrade_notice}
        <input type="submit" class="btn btn-primary cm-dialog-opener cm-dialog-auto-size" value="{$caption}" data-ca-target-id="content_upgrade_center_wizard_{$id}">
    {else}
        <input type="submit" name="dispatch[upgrade_center.install]" class="btn btn-primary cm-ajax cm-comet cm-dialog-closer" value="{__("install")}">
    {/if}
<!--install_button_{$id}--></div>