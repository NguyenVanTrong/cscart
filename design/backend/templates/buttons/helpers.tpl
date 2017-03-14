{* buttons *}
{function name="btn" text="" href="" title="" onclick="" target="" class="" data=[] form="" method=""}
    {$method = $method|upper}

    {if $href|fn_check_view_permissions:{$method|default:"GET"} && $dispatch|fn_check_view_permissions:{$method|default:"POST"}}
    {* base buttons *}
    {if $type == "text"}
        {if $href && $method == "POST"}
            {$class = "cm-post `$class`"}
        {/if}

        <a {if $target}target="{$target}"{/if} {if $href}href="{$href|fn_url}"{/if} {if $id}id="{$id}"{/if} {if $class}class="{$class}"{/if} {if $title}title="{$title}"{/if}
        {if $data}
            {foreach $data as $data_name=>$data_value}
                {if $data_value}
                    {$data_name}="{$data_value}"
                {/if}
            {/foreach}
        {/if}
        {if $onclick}onclick="{$onclick nofilter}; return false;"{/if}
        >
        {if $icon && $icon_first}<i class="{$icon}"></i>{/if}
        {$text}
        {if $icon && !$icon_first}<i class="{$icon}"></i>{/if}</a>
    {/if}

    {* shortcut for the list *}
    {if $type == "list"}
        {if !$href && !$process}
            {$class="cm-process-items cm-submit `$class`"}
        {/if}
        {$data['data-ca-target-form'] = $form}
        {$data['data-ca-dispatch'] = $dispatch}
        {btn type="text" target=$target href=$href data=$data class=$class onclick=$onclick text=$text method=$method}
    {/if}

    {* shortcut for the delete_selected *}
    {if $type == "delete_selected"}
        {if $icon}
            {$class="btn `$class`"}
            {$text=" "}
        {/if}
        {$data['data-ca-target-form'] = $form}
        {$data['data-ca-dispatch'] = $dispatch}
        {btn type="text" target=$target href=$href data=$data class="cm-process-items cm-submit cm-confirm `$class`" click=$click text=$text|default:__("delete_selected") method=$method}
    {/if}

    {* shortcut for the delete_selected *}
    {if $type == "delete"}
        {$data['data-ca-target-form'] = $form}
        {$data['data-ca-dispatch'] = $dispatch}
        {btn type="text" target=$target href=$href data=$data class="`$class`" click=$click text=$text|default:__("delete") method=$method}
    {/if}

    {* shortcut for the dialog *}
    {if $type == "dialog"}
        {btn type="text" text=$text class="cm-dialog-opener `$class`" href=$href id=$id title=$title data=['data-ca-target-id'=>$target_id, 'data-ca-target-form'=>$form] method=$method}
    {/if}

    {* shortcut for the multiple *}
    {if $type == "multiple"}
        {script src="js/tygh/node_cloning.js"}

        {assign var="tag_level" value=$tag_level|default:"1"}
        {strip}
            {if $only_delete != "Y"}
                {if !$hide_add}
                    <li>{btn type="text" onclick="Tygh.$('#box_' + this.id).cloneNode($tag_level); `$on_add`" id=$item_id method=$method}</li>
                {/if}

                {if !$hide_clone}
                    <li>{btn type="text" onclick="Tygh.$('#box_' + this.id).cloneNode($tag_level, true);" id=$item_id method=$method}</li>
                {/if}
            {/if}

            <li>{btn type="text" only_delete=$only_delete class="cm-delete-row" method=$method}</li>
        {/strip}
    {/if}

    {* shortcut for the add btn *}
    {if $type == "add"}
        {btn type="text" title=$title class="cm-tooltip btn" icon="icon-plus"  href=$href method=$method}
    {/if}

    {* shortcut for add button with text *}
    {if $type == "text_add"}
        {btn type="text" text=$text class="btn btn-primary `$class`" icon="icon-plus icon-white" icon_first=true href=$href method=$method}
    {/if}

    {/if}
{/function}

{* dropdown *}
{function name="dropdown" text="" title="" class="" content="" icon="" no_caret=false placement="left"}
    {if $content|strip_tags:false|replace:"&nbsp;":""|trim != ""}
        <div class="btn-group{if $placement == "left"} dropleft{/if} {$class}" {if $id}id="{$id}"{/if}>
            <a class="btn dropdown-toggle" data-toggle="dropdown" {if $title}title="{$title}"{/if}>
                <i class="{$icon|default:"icon-cog"}"></i>
                {if $text}
                    {$text|default:__("tools") nofilter}
                {/if}
                {if !$no_caret}
                    <span class="caret"></span>
                {/if}
            </a>
            <ul class="dropdown-menu">
                {$content nofilter}
            </ul>
        </div>
    {/if}
{/function}
