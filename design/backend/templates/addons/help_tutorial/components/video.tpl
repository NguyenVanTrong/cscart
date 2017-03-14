{strip}
<div class="help-tutorial-wrapper">
    <div class="help-tutorial-content clearfix {if $count == 2}help-tutorial-content_width_big{/if}{if $open} open{/if}" id="help_tutorial_content">
        {if $count == 2}
        <iframe width="460" height="360" src="//www.youtube.com/embed/{$item}?wmode=transparent&rel=0&html5=1" frameborder="0" allowfullscreen align="left"></iframe>
        <iframe width="460" height="360" src="//www.youtube.com/embed/{$item2}?wmode=transparent&rel=0&html5=1" frameborder="0" allowfullscreen align="right"></iframe>
        {else}
        <iframe width="640" height="360" src="//www.youtube.com/embed/{$item}?wmode=transparent&rel=0&html5=1" frameborder="0" allowfullscreen></iframe>
        {/if}
    </div>
</div>

<script type="text/javascript">
    (function(_, $) {
        $(function() {
            $('#help_tutorial_link').on('click', function() {
                var self = $(this);
                self.toggleClass('open');
                $('#help_tutorial_content').toggleClass('open');
            });
            if($('#elm_sidebar').length == 0) {
                $('#help_tutorial_link').css('margin-left', 0);
            }
            {if $open}
            $('#help_tutorial_link').addClass('open');
            {/if}
        });
    }(Tygh, Tygh.$));
</script>
{/strip}