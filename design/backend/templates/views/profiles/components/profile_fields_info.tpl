{assign var="first_field" value=true}
<p>
    {foreach from=$fields item=field name="fields"}
        {if !$field.field_name || $field.is_default == 'N'}
            {assign var="value" value=$user_data|fn_get_profile_field_value:$field}
            {if $value}
                {if $customer_info}
                    {if !$first_field}, {/if}<span class="additional-fields">
                {else}
                    <div class="control-group">
                {/if}
                {assign var="first_field" value=false}
                <label>{$field.description}:</label>
                {$value}

                {if $customer_info}
                    </span>
                {else}
                    </div>
                {/if}
            {/if}
        {/if}
    {/foreach}
</p>