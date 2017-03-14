<div class="addon-info pull-left">
    <small class="muted addon-version">{__("version")} {$addon_version|default:0.1}</small>
    {if $addon_supplier}
        {if $addon_supplier_link}
            <a href="{$addon_supplier_link}" target="_blank" class="muted addon-supplier">{$addon_supplier}</a>
        {else}
            <small class="muted addon-supplier">{$addon_supplier}</small>
        {/if}
    {/if}
    {if $addon_install_datetime}
        <small class="muted addon-installed-date">{$addon_install_datetime|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</small>
    {/if}
</div>