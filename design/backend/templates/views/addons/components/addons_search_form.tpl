<div class="sidebar-row">
    <h6>{__("search")}</h6>

    <form action="{""|fn_url}" name="addons_search_form" method="get" class="{$form_meta} addons-search-form">
        {$extra nofilter}
        
        <div class="sidebar-field ">
            <label for="elm_addon">{__("name")}</label>
            <input type="text" name="q" id="elm_addon" value="{$search.q}" size="30" />
            <i class="icon icon-remove hidden" id="elm_addon_clear" title="{__("remove")}"></i>
        </div>
        {if !$hide_for_vendor}
        <div class="sidebar-field">
            <label for="elm_addon_status">{__("status")}</label>

            <select id="elm_addon_status" name="type">
                <option value="any" {if empty($search.type) || $search.type == 'any'} selected="selected"{/if}>{__("any")}</option>
                <option value="not_installed" {if $search.type == 'not_installed'} selected="selected"{/if}>{__("not_installed")}</option>
                <option value="installed" {if $search.type == 'installed'} selected="selected"{/if}>{__("installed")}</option>
                <option value="active" {if $search.type == 'active'} selected="selected"{/if}>{__("active")}</option>
                <option value="disabled" {if $search.type == 'disabled'} selected="selected"{/if}>{__("disabled")}</option>
            </select>
        </div>
        <div class="sidebar-field">
            <label for="elm_addon_source">{__("addons_source")}</label>
            <select id="elm_addon_source" name="source">
                <option value="" {if empty($search.source)} selected="selected"{/if}>{__("any")}</option>
                <option value="core" {if $search.source == 'core'} selected="selected"{/if}>{__("addon_built_in")}</option>
                <option value="third_party" {if $search.source == 'third_party'} selected="selected"{/if}>{__("addon_third_party")}</option>
            </select>
        </div>
        {/if}

        <div class="sidebar-field">
            <input class="btn" type="submit" name="dispatch[{$dispatch}]" value="{__("search")}">
        </div>
    </form>
</div>
{if !$hide_for_vendor}
<script type="text/javascript">
    (function(_, $) {
        $(document).ready(function() {
            var
                $tables = $('.table-addons.cm-filter-table'),
                $addon_status = $('#elm_addon_status'),
                $addon_source = $('#elm_addon_source');

            $.ceEvent('on', 'ce.commoninit', function(context) {
                var $temp_tables = context.find('.table-addons.cm-filter-table');

                if ($temp_tables.length) {
                    $tables = $temp_tables;
                }
            });

            $.ceEvent('on', 'ce.filter_table_show_items', function (container, data) {
                if (!container.hasClass('table-addons')) {
                    return;
                }

                var status = $addon_status.val(),
                    source = $addon_source.val();

                switch (status) {
                    case 'not_installed':
                        data.items = data.items.filter('.filter_status_N');
                        break;
                    case 'installed':
                        data.items = data.items.filter('.filter_status_A,.filter_status_D');
                        break;
                    case 'active':
                        data.items = data.items.filter('.filter_status_A');
                        break;
                    case 'disabled':
                        data.items = data.items.filter('.filter_status_D');
                        break;
                }

                switch (source) {
                    case 'core':
                        data.items = data.items.filter('.filter_source_built_in');
                        break;
                    case 'third_party':
                        data.items = data.items.filter('.filter_source_third_party');
                        break;
                }
            });

            $addon_status.on('change', function() {
                $tables.ceFilterTable('filter');
            });
            $addon_source.on('change', function() {
                $tables.ceFilterTable('filter');
            });
        });
    }(Tygh, Tygh.$));
</script>
{/if}