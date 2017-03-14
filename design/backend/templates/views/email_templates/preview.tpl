<div title="{__("preview")}" id="preview_dialog">

{if $preview}
    <h4>{__("subject")}:</h4>
    <div>
        {$preview->getSubject()}
    </div>
    <h4>{__("body")}:</h4>
    <div>
        {$preview->getBody() nofilter}
    </div>
{/if}

<!--preview_dialog--></div>
