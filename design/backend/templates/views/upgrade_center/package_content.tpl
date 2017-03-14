<div id="package_content_{$package_id|replace:".":"_"}">

	<div class="tabs cm-j-tabs">
        <ul class="nav nav-tabs">
        	{if $content.files}
            	<li id="tab_files" class="cm-js active"><a>{__("files")}</a></li>
            {/if}
            
            {if $content.migrations}
            	<li id="tab_migrations" class="cm-js"><a>{__("migrations")}</a></li>
            {/if}

			{if $content.languages}
            	<li id="tab_languages" class="cm-js"><a>{__("languages")}</a></li>
            {/if}
        </ul>
    </div>
	
	<div class="cm-tabs-content">
	    {if $content.files}
		    <div id="content_tab_files">
				<table class="table table-condensed">
				    <thead>
				        <tr>
				            <th>{__("files")}</th>
				            <th class="right">{__("action")}</th>
				        </tr>
				    </thead>
				    <tbody>
				    	{foreach $content.files as $file_path => $file_data}
					        <tr>
					            <td>
					                {$file_path}
					            </td>
					            <td width="10%" class="right">
					            	{if $file_data.status == "changed"}
					            		<span class="label label-warning">{__("change")}</span>
					            	{elseif $file_data.status == "deleted"}
					            		<span class="label label-important">{__("delete")}</span>
					            	{elseif $file_data.status == "new"}
					            		<span class="label label-info">{__("create")}</span>
					            	{/if}
					                
					            </td>
					        </tr>
				        {/foreach}
				    </tbody>
				</table>
		    </div>
	    {/if}

	    {if $content.migrations}
		    <div class="hidden" id="content_tab_migrations">
				<table class="table table-condensed">
				    <thead>
				        <tr>
				            <th>{__("migrations")}</th>
				        </tr>
				    </thead>
				    <tbody>
				    	{foreach $content.migrations as $migration}
					        <tr>
					            <td>
					                {$migration}
					            </td>
					        </tr>
				        {/foreach}
				    </tbody>
				</table>
		    </div>
	    {/if}
	    
	    {if $content.languages}
		    <div id="content_tab_languages" class="hidden">
				<table class="table table-condensed">
				    <thead>
				        <tr>
				            <th>{__("languages")}</th>
				        </tr>
				    </thead>
				    <tbody>
				    	{foreach $content.languages as $language}
					        <tr>
					            <td>
					                {$language}
					            </td>
					        </tr>
				        {/foreach}
				    </tbody>
				</table>
		    </div>
	    {/if}
    </div>

    <div class="buttons-container">
	    <a class="cm-dialog-closer cm-cancel tool-link btn">{__("close")}</a>
	</div>


<!--package_content_{$package_id|replace:".":"_"}--></div>