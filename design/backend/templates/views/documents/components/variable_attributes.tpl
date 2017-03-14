{if $attributes}
<ul class="hidden nav nav-list" >
    {foreach from=$attributes key="attribute" item="item"}
        <li><span class="cm-emltpl-insert-variable label hand" data-ca-template-value="{$variable}.{$attribute}" data-ca-target-template="{$template}">{$attribute}</span>
            {if $item|is_array}
                <span class="icon-plus hand nav-opener" {if $item|is_array}style="white-space:nowrap;"{/if}></span>
                {include file="views/documents/components/variable_attributes.tpl" attributes=$item variable="`$variable`.`$attribute`" template=$template}
            {/if}
        </li>
    {/foreach}
</ul>
{/if}