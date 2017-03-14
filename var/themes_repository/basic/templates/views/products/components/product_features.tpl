{foreach from=$product_features item="feature"}
    {if $feature.feature_type != "ProductFeatures::GROUP"|enum}
        <div class="control-group">
        {if $feature.full_description|trim}{include file="common/help.tpl" text=$feature.description content=$feature.full_description id=$feature.feature_id show_brackets=true wysiwyg=true}{/if}
        <label>{$feature.description nofilter}:</label>

        {if $feature.feature_type == "ProductFeatures::MULTIPLE_CHECKBOX"|enum}
            {assign var="hide_affix" value=true}
        {else}
            {assign var="hide_affix" value=false}
        {/if}

        {strip}
        <div class="feature-value">
            {if $feature.prefix && !$hide_affix}{$feature.prefix}{/if}
            {if $feature.feature_type == "ProductFeatures::SINGLE_CHECKBOX"|enum}
            <span class="compare-checkbox" title="{$feature.value}">{if $feature.value == "Y"}<i class="icon-ok"></i>{/if}</span>
            {elseif $feature.feature_type == "ProductFeatures::DATE"|enum}
                {$feature.value_int|date_format:"`$settings.Appearance.date_format`"}
            {elseif $feature.feature_type == "ProductFeatures::MULTIPLE_CHECKBOX"|enum && $feature.variants}
                <ul class="no-markers no-margin">
                {foreach from=$feature.variants item="var"}
                    {assign var="hide_variant_affix" value=!$hide_affix}
                    {if $var.selected}<li><span class="compare-checkbox" title="{$var.variant}"><i class="icon-ok"></i></span>{if !$hide_variant_affix}{$feature.prefix}{/if}{$var.variant}{if !$hide_variant_affix}{$feature.suffix}{/if}</li>{/if}
                {/foreach}
                </ul>
            {elseif in_array($feature.feature_type, ["ProductFeatures::TEXT_SELECTBOX"|enum, "ProductFeatures::EXTENDED"|enum, "ProductFeatures::NUMBER_SELECTBOX"|enum])}
                {foreach from=$feature.variants item="var"}
                    {if $var.selected}{$var.variant}{/if}
                {/foreach}
            {elseif $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum}
                {$feature.value_int|floatval|default:"-"}
            {else}
                {$feature.value|default:"-"}
            {/if}
            {if $feature.suffix && !$hide_affix}{$feature.suffix}{/if}
        </div>
        {/strip}
        </div>
    {/if}
{/foreach}

{foreach from=$product_features item="feature"}
    {if $feature.feature_type == "ProductFeatures::GROUP"|enum && $feature.subfeatures}
        {include file="common/subheader.tpl" title=$feature.description tooltip=$feature.full_description text=$feature.description}
        {include file="views/products/components/product_features.tpl" product_features=$feature.subfeatures}
    {/if}
{/foreach}