<ul>
	{foreach from=$list item="item"}
		<li>
			<a>{$item.name}</a>
			{if $item.childs}
				{include file="backend:views/debugger/components/templates_list.tpl" list=$item.childs}
			{/if}
		</li>
	{/foreach}
</ul>
