(function(_, $) {

    $(_.doc).on('click', '.cm-emltpl-restore-default', function() {
        var self = $(this);
        var def_input = $('#' + self.data('caDefaultId'));
        var input = $('#' + self.data('caTargetId'));

        if (input.hasClass('cm-wysiwyg')) {
            input.ceEditor('val', def_input.val());
        } else {
            input.val(def_input.val());
        }
    });

    $(_.doc).on('click', '.cm-emltpl-insert-variable', function() {
        var self = $(this);
        var active_input = self.data('caTargetTemplate') ? $('#'+self.data('caTargetTemplate')) : $('.cm-emltpl-set-active.cm-active');
        if (active_input.length) {
            if (active_input.hasClass('cm-wysiwyg')) {
                active_input.ceEditor('insert', '{{ '+self.data('caTemplateValue')+' }}');
                return;
            } else {
                active_input.ceInsertAtCaret('{{ '+self.data('caTemplateValue')+' }}')    
            }
        }
    });

    $(_.doc).on('focus', '.cm-emltpl-set-active', function() {
        var self = $(this);
        $('.cm-emltpl-set-active').removeClass('cm-active');
        self.addClass('cm-active');
    });

}(Tygh, Tygh.$));
