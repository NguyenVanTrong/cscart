<div id="content_tags">

{script src="js/addons/tags/tags_autocomplete.js"}

    <div class="control-group {if $allow_save}cm-no-hide-input{/if}">
        <label class="control-label">{__("tags")}:</label>
        <div class="controls">
            <ul id="my_tags">
                <input type="hidden" id="object_id" value={$object_id} />
                <input type="hidden" id="object_type" value={$object_type} />
                <input type="hidden" name="{$input_name}[tags][]" value="" />
                <input type="hidden" id="object_name" value="{$input_name}[tags][]" />
                {foreach from=$object.tags item="tag" name="tags"}<li>{$tag.tag}</li>{/foreach}
            </ul>
        </div>
    </div>
</div>
