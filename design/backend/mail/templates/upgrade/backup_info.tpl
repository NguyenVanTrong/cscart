{include file="common/letter_header.tpl"}

{if $backup_file}
    {__("uc_restore_email_body", ["[backup_file]" => $backup_file, "[settings_section]" => $settings_section_url])}
    <p>
    {$restore_link}
    </p>
{else}
    {__("uc_open_store_email_body", ["[settings_section]" => $settings_section_url])}
{/if}

{include file="common/letter_footer.tpl"}