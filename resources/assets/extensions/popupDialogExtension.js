/**
 * Created by Pavel on 29.5.2015.
 */
(function ($, undefined) {

    $.nette.ext('popupDialog', {
        load: function ($name, $callback) {
            this.load($('body'));
        },
        before: function(response, sender) {

            if (!jQuery.isEmptyObject(sender.nette)) {
                if (sender.nette.isSubmit) {
                    $(this.selectorProgress).addClass('in');
                }
                if ($(sender.nette.el).is(".ajax")) {
                    $(this.selectorProgress).addClass('in');
                }
            }
        },
        success: function(response, q, e, sender) {
            var snippets = this.ext('snippets', true);

            /** @todo bug, objevuje se nápis snippet */
            //$.nette.ext('snippets').updateSnippets(snippets);

            if (!jQuery.isEmptyObject(sender.nette)) {
                var dialog = $(this.selectorDialog);
                if (dialog.length) {
                    if (dialog.find('#dPopupDialog').data('auto-close')) {
                        $this = this;

                        if (this.ajaxRedrawUrl) {
                            $.nette.ajax({
                                url: this.ajaxRedrawUrl,
                                processData: true,
                                //off: ['spinner'],
                                success: function (payload) {
                                    $($this.selectorDialog).modal('hide');
                                    $('.progress').removeClass('active').removeClass('in');
                                    return payload;
                                }
                            });

                        } else {
                            $('.progress').removeClass('active').removeClass('in');
                        }
                    }
                }
            }
        }
        }, {
            /**
             * @param target
             */
            load: function (target) {
                var $this = this;

                $(target.find(this.selector).click(function (event) {
                    var obj = this;
                    var id = $this.creatorDialog;
                    var url = event.currentTarget.href;
                    var popupType = $(obj).data('popup-type');
                    if (popupType == undefined) popupType = ''; else popupType = ' ' + popupType;
                    var autoClose = $(obj).data('auto-close');
                    if (autoClose == undefined) autoClose = true;

                    event.preventDefault();
                    event.stopImmediatePropagation();

                    $("<div id='"+id+"' class='modal fade' tabindex='-1' role='dialog' aria-labelledby='dModalLabel' aria-hidden='true'></div>").appendTo('body');
                    $('#dPopup').html("<div id='dPopupDialog' class='modal-dialog' data-modal-autoclose='" + autoClose + "'></div>");
                    $('#dPopupDialog').html("<div id='dPopupContent' class='modal-content" + popupType + "'></div>");
                    $('#dPopupContent').html("<div id='dPopupHeader' class='modal-header'></div><div id='dPopupBody' class='modal-body'><div class='progress progress-striped active'><div style='width: 100%' class='progress-bar'>Nahrávám ...</div></div></div>");
                    $('#dPopupHeader').html("<a class='close' data-dismiss='modal' aria-hidden='true'>×</a><h4 class='modal-title' id='dPopupTitle'></h4>");

                    $("<div id='dPopupFooter' class='modal-footer'>").appendTo('#dPopupContent');
                    $('#dPopupFooter').html("<div class='progress progress-striped active fade'><div style='width: 100%' class='progress-bar'>Pracuji ...</div></div>");
                    $('#dPopupTitle').html($(obj).data('popup-title'));

                    if ($(obj).data('popup-header-class')) {
                        $('#dPopupHeader').addClass($(obj).data('popup-header-class'));
                    }
                    $($this.selectorDialog).on('hidden.bs.modal', function () {
                        $('#dPopup').remove();
                    }).on('show.bs.modal', function () {
                        // fix firefox bug with select-box in popup
                        $.fn.modal.Constructor.prototype.enforceFocus = function () { };
                    }).modal('show');

                    $.nette.ajax({
                        url: url,
                        processData: true,
                        off: ['spinner'],
                        success: function (payload) {
                            $('#dPopupBody').html(payload);

                            for (var i = 0; i < document.forms.length; i++) {
                                Nette.initForm(document.forms[i]);
                            }

                            return payload;
                        }
                    });

                    return false;
                }));
            },

            selector: '[data-modal-dialog]',
            creatorDialog: 'dPopup',
            selectorDialog: "#dPopup",
            selectorProgress: "#dPopupFooter .progress",
            ajaxRedrawUrl: ''
        }
    );

})(jQuery);
