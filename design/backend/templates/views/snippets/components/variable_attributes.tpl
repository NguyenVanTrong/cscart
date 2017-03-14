{foreach from=$attributes key="attribute" item="item"}
    {if $item|is_array}
        {include file="views/snippets/components/variable_attributes.tpl" attributes=$item name="`$name`.`$attribute`"}
    {else}
        <span class="label hand  cm-emltpl-insert-variable" data-ca-template-value="{$name}.{$attribute}" data-ca-target-template="elm_table_column_value_{$id}">{ldelim}{ldelim} {$name}.{$attribute} {rdelim}{rdelim}</span>
    {/if}
{/foreach}