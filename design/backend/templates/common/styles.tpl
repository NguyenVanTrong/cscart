{styles}
	{style src="ui/jqueryui.css"}
	{style src="lib/select2/select2.min.css"}
    {hook name="index:styles"}
        {style src="styles.less"}
        {style src="glyphs.css"}

        {include file="views/statuses/components/styles.tpl" type=$smarty.const.STATUSES_ORDER}

        {if $language_direction == 'rtl'}
            {style src="rtl.less"}
        {/if}
    {/hook}
    {style src="font-awesome.css"}
{/styles}