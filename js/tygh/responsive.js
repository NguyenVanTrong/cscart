(function(_, $) {
    'use strict';

    // ui module
    var ui = (function() {
        return {
            winWidth: function() {
                return $(window).width();
            },

            responsiveScroll: function() {
                $.ceEvent('on', 'ce.needScroll', function(opt) {

                    opt.need_scroll = false;
                    setTimeout(function() {
                        $.scrollToElm(opt.jelm.data('caScroll'));
                    }, 310);
                });
            },

            responsiveNotifications: function() {
                if(this.winWidth() <= 767) {
                    $.ceEvent('on', 'ce.notificationshow', function(notification) {
                        if($(notification).hasClass('cm-notification-content-extended')) {
                            $('body,html').scrollTop(0);
                        }
                    });
                }
            },

            responsiveTabs: function() {
                if(this.winWidth() <= 480) {           
                    // conver tabs to accordion
                    $('.cm-j-tabs:not(.cm-j-tabs-disable-convertation)').each(function(index) {
                        var accordion = $('<div class="ty-accordion cm-accordion" id="accordion_id_' + index + '">');
                        var tabsContent = $('.cm-tabs-content:not(.cm-j-content-disable-convertation)');
                        var self = this;

                        // hide tabs
                        $(this).hide();
                        tabsContent.hide();

                        if(!$('#accordion_id_' + index).length) {

                            $(this).find('>ul>li').each(function() {
                                var id = $(this).attr('id');
                                var content = $('.cm-tabs-content > #content_' + id).show();

                                // rename tab id
                                $(this).attr('id', 'hidden_tab_' + id);

                                accordion.append('<h3 id="' + id + '">' + $(this).text() + '</h3>');
                                $(content).appendTo(accordion);

                            });

                            $(self).before(accordion);
                        }
                    });

                    $('.cm-accordion').ceAccordion('reinit', {
                        animate: 300,
                        heightStyle : "content",
                        activate: function(event, ui) {
                            var accordionTop = $(this).scrollTop(),
                                headerOffset  = $(ui.newHeader);

                            if (headerOffset.length > 0) {
                                if ("-ms-user-select" in document.documentElement.style && navigator.userAgent.match(/IEMobile\/10\.0/)) {
                                    setTimeout(function() {
                                        $('html, body').scrollTop(accordionTop + headerOffset.offset().top  - 10);
                                    }, 300);
                                } else {
                                    $('html, body').animate({
                                        scrollTop: accordionTop + headerOffset.offset().top - 10
                                    }, 300);
                                }
                            }
                        }
                    });

                    var active = _.anchor;
                    if(typeof active !== 'undefined' && $(active).length > 0) {
                        $(active).click();
                    }

                } else {

                    $('.cm-accordion').accordion('destroy');

                    $('.cm-accordion > div').each(function(index) {
                        $(this).hide();
                        $(this).appendTo($('.cm-tabs-content'));
                    });

                    $('.cm-accordion').remove();

                    // remove prefix
                    $('.cm-j-tabs>ul>li').each(function(){
                        var id = $(this).attr('id').replace('hidden_tab_','');
                        $(this).attr('id', id);

                        $('#content_' + $(this).attr('id')).css('display','');
                    });

                    $('.cm-j-tabs, .cm-tabs-content').show();
                }
            },

            responsiveMenu: function() {

                var whichEvent = ('ontouch' in document.documentElement ? "touch" : "click");

                // FIXME Windows IE 8 doesn't have touch event
                if(_.isTouch && window.navigator.msPointerEnabled) {
                    whichEvent = 'click';
                }

                if($('.ty-menu__menu-btn').data('ca-responsive-menu') !== true) {

                    $('.ty-menu__menu-btn').on(whichEvent, function(e) {
                        var menu_elm = $('.cm-responsive-menu');
                        $(this).parent(menu_elm).find('.ty-menu__item').toggle();
                    });

                    $('.cm-responsive-menu-toggle').on(whichEvent,function (e) {
                        $(this).toggleClass('ty-menu__item-toggle-active');
                        $('.icon-down-open', this).toggleClass('icon-up-open');
                        $(this).parent().find('.cm-responsive-menu-submenu').first().toggleClass('ty-menu__items-show');
                    });

                    $('.ty-menu__menu-btn').data('ca-responsive-menu', true);
                }

                if(_.isTouch == false && ui.winWidth() >= 767) {
                    $('.cm-responsive-menu').on('mouseover mouseout', function(e) {
                        ui.detectMenuWidth(e);
                    });
                }

            },

            responsiveMenuLargeTouch: function(e) {
                var elm = $(e.target);
                var menuWidth = $('.cm-responsive-menu').width();
                if (ui.winWidth() >= 767 && e.type == 'touchstart') {                    
                    
                    var menuItem = elm.hasClass('cm-menu-item-responsive') ? elm : elm.closest('.cm-menu-item-responsive');
                    if (!menuItem.hasClass('is-hover-menu') && menuItem.find('.ty-menu__submenu-items').length > 0) {
                        e.preventDefault();
                        menuItem.siblings('.cm-menu-item-responsive').removeClass('is-hover-menu');
                        menuItem.addClass('is-hover-menu');
                    }
                    
                    var subMenu = $('.ty-menu__submenu-items');
                    if (subMenu.is(':visible') && !elm.closest('.cm-menu-item-responsive').length) {
                        $('.cm-menu-item-responsive').removeClass('is-hover-menu');
                    }

                } else {
                    $('.cm-menu-item-responsive').removeClass('is-hover-menu');
                }

                ui.detectMenuWidth(e);
            },

            detectMenuWidth: function(e) {
                var elm = $(e.target);
                var menuElm = elm.parents(".cm-responsive-menu");
                var isHorisontalMenu = menuElm.parent().hasClass("ty-menu-vertical") ? false : true;

                if(isHorisontalMenu) {
                    var menuWidth = menuElm.outerWidth();
                    var menuOffset = menuElm.offset();
                    var menuItemElm = elm.closest('.cm-menu-item-responsive');
                    $('.ty-menu__submenu-to-right').removeClass('ty-menu__submenu-to-right');
                    var submenu, position;

                    // Detect menu collisions
                    if(menuItemElm) {
                        if(typeof menuItemElm.offset()  !== "undefined") {
                            menuWidth = menuWidth - (menuItemElm.offset().left - menuOffset.left)
                        }
                        submenu = $('.cm-responsive-menu-submenu', menuItemElm).first();
                        
                        if(submenu.length) {
                            submenu.css({visibility: "hidden", left: 0});
                            position = submenu.outerWidth();
                            if(position > menuWidth) {
                                submenu.parent().addClass('ty-menu__submenu-to-right');
                            }
                            submenu.css({visibility: "", left: "auto"});
                        }

                    }
                }
            },

            responsiveTables: function(e) {

                var tables = $('.ty-table');

                if(this.winWidth() <= 767) {
                    tables.each(function() {
                        var thTexts = [];

                        // if we have sub table detach it.
                        var subTable = $(this).find('.ty-table');                        
                        
                        if(subTable.length) {

                            var subTableStack = [];
                            subTable.each(function (index) {
                                $(this).parent().attr('data-ca-has-sub-table_' + index , 'true');
                                subTableStack.push($(this).detach());
                            });
                            
                        }

                        $(this).find('th:not(.ty-table-disable-convertation)').each(function() {
                            thTexts.push($(this).text());
                        });

                        $(this).find('tr:not(.ty-table__no-items)').each(function() {
                            $(this).find('td:not(.ty-table-disable-convertation)').each(function(index) {
                                var $elm = $(this);

                                if($elm.find('.ty-table__responsive-content').length == 0) {
                                    $elm.wrapInner('<div class="ty-table__responsive-content"></div>');
                                    $elm.prepend('<div class="ty-table__responsive-header">' + thTexts[index] + '</div>');
                                }
                            });

                        });

                        if (subTable.length) {
                            subTable.each(function (index) {
                                var subTableElm = $('[data-ca-has-sub-table_' + index + ']');
                                subTableElm.prepend(subTableStack[index]);
                                
                                subTableElm.removeAttr('data-ca-has-sub-table_' + index);
                            });
                        }
                    });
                }
            },

            resizeDialog: function() {
                var self = this;
                var dlg = $('.ui-dialog');

                $('.ui-widget-overlay').css({
                    'min-height': $(window).height()
                });

                $(dlg).css({
                    'position':'absolute',
                    'width': $(window).width() - 20,
                    'left': '10px',
                    'top':'10px',
                    'max-height': 'none',
                    'height': 'auto',
                    'margin-bottom': '10px'
                });

                // calculate title width
                $(dlg).find('.ui-dialog-title').css({
                    'width': $(window).width() - 80
                });

                $(dlg).find('.ui-dialog-content').css({
                    'height': 'auto',
                    'max-height': 'none'
                });

                $(dlg).find('.object-container').css({
                    'height': 'auto'
                });

                $(dlg).find('.buttons-container').css({
                    'position':'relative',
                    'top': 'auto',
                    'left': '0px',
                    'right': '0px',
                    'bottom': '0px',
                    'width': 'auto'
                });

            },

            responsiveDialog: function() {
                var self = this;
                $.ceEvent('on', 'ce.dialogshow', function() {
                    if(self.winWidth() <= 767) {
                        self.resizeDialog();
                        $('body,html').scrollTop(0);
                    }
                });
            },

            responsiveFilters: function(e) {
                var filtersContent = $('.cm-horizontal-filters-content');
                if(this.winWidth() <= 767) {
                    filtersContent.removeClass('cm-popup-box');
                } else {
                    filtersContent.addClass('cm-popup-box');
                }

                if(this.winWidth() > 767) {
                    $('.ty-horizontal-filters-content-to-right').removeClass('ty-horizontal-filters-content-to-right');
                    $('.ty-horizontal-product-filters-dropdown').click(function() {
                        var hrFiltersWidth = $(".cm-horizontal-filters").width();
                        var hrFiltersContent = $('.cm-horizontal-filters-content', this);
                        setTimeout(function () {
                            var position = hrFiltersContent.offset().left + hrFiltersContent.width();
                            if(position > hrFiltersWidth) {
                                hrFiltersContent.addClass("ty-horizontal-filters-content-to-right");
                            }
                        }, 1);
                    });
                }
            },

            responsiveInlineTextLinksLargeTouch: function(e) {
                var elm = $(e.target);
                if (ui.winWidth() >= 767 && e.type == 'touchstart') {

                    var linksItem = elm.hasClass('ty-text-links__item') ? elm : elm.closest('.ty-text-links__item');
                    if (!linksItem.hasClass('is-hover-link') && linksItem.hasClass('ty-text-links__subitems')) {
                        e.preventDefault();
                        linksItem.siblings('.ty-text-links__item').removeClass('is-hover-link');
                        linksItem.addClass('is-hover-link');
                    }

                } else {
                    $('.ty-text-links__item').removeClass('is-hover-link');
                }
            }
        };
    })();

    // Init
    $(document).ready(function(){

        $(window).resize(function(e){
            ui.winWidth();
            ui.responsiveTables();
            ui.responsiveFilters();
            if(ui.winWidth() <= 767) {
                ui.resizeDialog();
            }
        });

        if (window.addEventListener) {
            window.addEventListener('orientationchange', function() {
                if(ui.winWidth() <= 767) {
                    ui.resizeDialog();
                }
                $.ceDialog('get_last').ceDialog('reload');
            }, false);
        }

        ui.responsiveDialog();

        // notifications
        ui.responsiveNotifications();

        // responsive tables
        ui.responsiveTables();

        // responsive filters
        ui.responsiveFilters();

        $.ceEvent('on', 'ce.ajaxdone', function() {
            ui.responsiveTables();
            ui.responsiveMenu();
            ui.responsiveFilters();

            if(ui.winWidth() <= 767) {
                ui.resizeDialog();
            }
        });

        // Menu and Inline text links init
        ui.responsiveMenu();
        $(document).on('touchstart', function(e) {
            var elm = $(e.target);
            // Menu
            if (elm.hasClass('cm-menu-item-responsive') || elm.closest('.cm-menu-item-responsive').length) {
                ui.responsiveMenuLargeTouch(e);    
            } else {
                $('.is-hover-menu').removeClass('is-hover-menu');
            }

            // Inline text links
            if (elm.hasClass('ty-text-links__subitems') || elm.closest('.ty-text-links__subitems').length) {
                ui.responsiveInlineTextLinksLargeTouch(e);
            } else {
                $('.is-hover-link').removeClass('is-hover-link');
            }
        });

    });

     // tabs
    $.ceEvent('on', 'ce.tab.init', function() {
        $(window).resize(function(e){
            ui.responsiveTabs();
        });
        ui.responsiveTabs();
        ui.responsiveScroll();
    });

}(Tygh, Tygh.$));
