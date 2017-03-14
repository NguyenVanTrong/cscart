{if $settings.Appearance.calendar_date_format == "month_first"}
    {assign var="date_format" value="%m/%d/%Y"}
{else}
    {assign var="date_format" value="%d/%m/%Y"}
{/if}

<div class="calendar">
    <input type="text" id="{$date_id}" name="{$date_name}" class="{if $date_meta}{$date_meta}{/if} cm-calendar" value="{if $date_val}{$date_val|fn_parse_date|date_format:"`$date_format`"}{/if}" {$extra nofilter} size="10" />
    {if $show_time}
    <input class="input-time" size="5" maxlength="5" type="text" name="{$time_name}" value="{if $date_val}{$date_val|fn_parse_date|date_format:"%H:%M"}{/if}" placeholder="00:00" />
    {/if}
    <span data-ca-external-focus-id="{$date_id}" class="icon-calendar cm-external-focus"></span>
</div>

<script type="text/javascript">
(function(_, $) {$ldelim}
    $.ceEvent('on', 'ce.commoninit', function(context) {
        $('#{$date_id}').datepicker({
            changeMonth: true,
            duration: 'fast',
            changeYear: true,
            numberOfMonths: 1,
            selectOtherMonths: true,
            showOtherMonths: true,
            firstDay: {if $settings.Appearance.calendar_week_format == "sunday_first"}0{else}1{/if},
            dayNamesMin: ['{__("weekday_abr_0")|escape:"javascript"}', '{__("weekday_abr_1")|escape:"javascript"}', '{__("weekday_abr_2")|escape:"javascript"}', '{__("weekday_abr_3")|escape:"javascript"}', '{__("weekday_abr_4")|escape:"javascript"}', '{__("weekday_abr_5")|escape:"javascript"}', '{__("weekday_abr_6")|escape:"javascript"}'],
            monthNamesShort: ['{__("month_name_abr_1")|escape:"javascript"}', '{__("month_name_abr_2")|escape:"javascript"}', '{__("month_name_abr_3")|escape:"javascript"}', '{__("month_name_abr_4")|escape:"javascript"}', '{__("month_name_abr_5")|escape:"javascript"}', '{__("month_name_abr_6")|escape:"javascript"}', '{__("month_name_abr_7")|escape:"javascript"}', '{__("month_name_abr_8")|escape:"javascript"}', '{__("month_name_abr_9")|escape:"javascript"}', '{__("month_name_abr_10")|escape:"javascript"}', '{__("month_name_abr_11")|escape:"javascript"}', '{__("month_name_abr_12")|escape:"javascript"}'],
            yearRange: '{if $start_year}{$start_year}{else}c-100{/if}:c+10',
            {if $min_date || $min_date === 0}minDate: {$min_date},{/if}
            {if $max_date || $max_date === 0}maxDate: {$max_date},{/if}
            dateFormat: '{if $settings.Appearance.calendar_date_format == "month_first"}mm/dd/yy{else}dd/mm/yy{/if}'
        });
    });
{$rdelim}(Tygh, Tygh.$));
</script>
