(function(_, $) {

    if (Retina.isRetina()) {
        $(document).ready(function() {
            document.addEventListener("DOMNodeInserted", function (ev) {
                if (ev.target.tagName == 'IMG') { // pure js to speed up operation
                    new RetinaImage(ev.target, true);
                } else {
                    $(ev.target).find('img').each(function(i, elm) {
                        $(elm).on('load', function() {
                            new RetinaImage(elm, true);
                        });
                    });
                }
            }, false);
        });
    }
    
}(Tygh, Tygh.$));
