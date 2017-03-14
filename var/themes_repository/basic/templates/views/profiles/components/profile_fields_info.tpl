{foreach from=$fields item=field}
    {assign var="value" value=$user_data|fn_get_profile_field_value:$field}
    {if $value}
        <div class="info-field {$field.field_name|replace:"_":"-"}">
            {if $field.is_default == "N"}
                <label>{$field.description}:</label>
            {/if}
            {$value}
        </div>
    {/if}
{/foreach}