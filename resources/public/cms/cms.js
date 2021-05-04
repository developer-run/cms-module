$(function(){

    $('input[type="file"].auto-upload').change(function (e) {
        var form = $(this).closest('form');

        console.log(Nette.validateForm(form.get(0)));


        if (Nette.validateForm(form.get(0))) {
            console.log(form);

            $(form).submit();
        }
    });


    $(document).on('change', 'select.auto-change', function () {
        var form = $(this).closest('form');
        if (Nette.validateForm(form.get(0))) {
            $(form).submit();
        }
    });


    $('select.auto-change').change(function (e) {
    });


    $(document).on('click', '.onClickFileUpload', function () {
        var input = $(this).closest('[data-has-file-upload]').find('form input[type="file"]');
        if (input) {
            $(input).click();
        }
    });


});

(function($, undefined) {

    $.nette.ext('signalLoading', {
        before: function(xhr, settings) {
            if (settings.nette && settings.nette.el.data('ajax-signal-loading') == true) {
                if (settings.nette.el.data('target')) {
                    var selectorAnimationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';

                    $(settings.nette.el.data('target')).addClass('loading');
                }
            }
        },
        complete: function(payload, status, xhr, settings) {

            // console.log(payload);
            // console.log(settings);

            if (settings.nette && settings.nette.el.data('ajax-signal-loading') == true) {


                if (settings.nette.el.data('target')) {



                    $(settings.nette.el.data('target')).removeClass('loading');
                    // .addClass('loaded').one(selectorAnimationEnd, function() {
                    // $(this).removeClass('loaded');
                    // });
                }
            }
        }

    });


    $.nette.ext('modalClose', {
        success: function(payload, status, xhr, settings) {

            // if (settings.nette && settings.nette.isSubmit && settings.nette.form.data('name') == 'userForm') {
            if (settings.nette && settings.nette.el.is("[data-dismiss='modal']")) {
                console.log("modla hide");
                $('.modal.in').modal('hide');
            }
        }

    });


    $.nette.ext('pageProgress',
        {
            start: function()
            {
                var selectorAnimationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
                var effect = 'animated flash';
                var el = ".nav.nav-tabs";

                // $(el).css('border', '1px solid red');
                // $(el).addClass(effect).one(selectorAnimationEnd, function() {
                //     $(this).removeClass(effect);
                // });
                //
                // $('.nav-tabs-custom').css('border', '1px solid red');
                // $('.nav.nav-tabs').css('background', 'red');
            },

            complete: function()
            {
                // $('.nav-tabs-custom').css('border', '');
                // $('.nav.nav-tabs').css('background', '');

            }
        });


    $.nette.ext('ckeInline', {
        complete: function() {

            var introduction = $('[contenteditable]:not(.cke_editable)');

            console.log($(introduction).length);
            return;

            $.each(introduction, function(index, value) {

                console.log(this);

                CKEditor.InlineEditor
                    .create(document.querySelector('#editor'), {
                        toolbar: [  'bold', 'italic', 'link' ]

                    })
                    .then(newEditor => {
                        editor = newEditor;
                    })
                    .catch(error => {
                        console.error(error);
                    });



                /*
                CKEDITOR.inline( this, {
                    // Allow some non-standard markup that we used in the introduction.
                    // extraAllowedContent: 'a(documentation);abbr[title];code',
                    // removePlugins: 'stylescombo',
                    removePlugins: 'sourcearea',
                    extraPlugins: 'sourcedialog'
                    // Show toolbar on startup (optional).
                    // startupFocus: true

                } );
                */

            });
        }
    });


    let editor;


    /**
     * @param editor InlineEditor
     * @constructor
     */
    function ConvertDivAttributes( editor ) {
        // Allow <div> elements in the model.
        editor.model.schema.register( 'div', {
            allowWhere: '$block',
            allowContentOf: '$root'
        } );

        // Allow <div> elements in the model to have all attributes.
        editor.model.schema.addAttributeCheck( context => {
            if ( context.endsWith( 'div' ) ) {
                return true;
            }
        } );

        // View-to-model converter converting a view <div> with all its attributes to the model.
        editor.conversion.for( 'upcast' ).elementToElement( {
            view: 'div',
            model: ( viewElement, modelWriter ) => {
                return modelWriter.createElement( 'div', viewElement.getAttributes() );
            }
        } );

        // Model-to-view converter for the <div> element (attributes are converted separately).
        editor.conversion.for( 'downcast' ).elementToElement( {
            model: 'div',
            view: 'div'
        } );

        // Model-to-view converter for <div> attributes.
        // Note that a lower-level, event-based API is used here.
        editor.conversion.for( 'downcast' ).add( dispatcher => {
            dispatcher.on( 'attribute', ( evt, data, conversionApi ) => {
                // Convert <div> attributes only.
                if ( data.item.name != 'div' ) {
                    return;
                }

                const viewWriter = conversionApi.writer;
                const viewDiv = conversionApi.mapper.toViewElement( data.item );

                // In the model-to-view conversion we convert changes.
                // An attribute can be added or removed or changed.
                // The below code handles all 3 cases.
                if ( data.attributeNewValue ) {
                    viewWriter.setAttribute( data.attributeKey, data.attributeNewValue, viewDiv );
                } else {
                    viewWriter.removeAttribute( data.attributeKey, viewDiv );
                }
            } );
        } );
    }

    class AllowClassesPlugin2 {
        constructor( editor ) {
            this.editor = editor;
        }

        init() {
            const editor = this.editor;

            editor.model.schema.extend( 'table', {
                allowAttributes: 'class'
            } );

            editor.conversion.attributeToAttribute( {
                model: {
                    name: 'table',
                    key: 'class',
                    values: [ 'big', 'small' ]
                },
                view: {
                    big: {
                        name: 'figure',
                        key: 'class',
                        value: [ 'table', 'some-big-table' ]
                    },

                    small: {
                        name: 'figure',
                        key: 'class',
                        value: [ 'table', 'some-big-small' ]
                    }
                }
            } );

            console.log(editor);

        }
    }


    function AllowClassesPlugin(editor) {
        editor.model.schema.extend( 'table', {
            allowAttributes: 'class'
        } );

        editor.conversion.attributeToAttribute( {
            model: {
                name: 'table',
                key: 'class',
                values: [ 'big', 'small' ]
            },
            view: {
                big: {
                    name: 'figure',
                    key: 'class',
                    value: [ 'table', 'some-big-table' ]
                },

                small: {
                    name: 'figure',
                    key: 'class',
                    value: [ 'table', 'some-big-small' ]
                }
            }
        } );
    }


    function AllowSourcePlugin(editor) {

        // A simple conversion from the `source` model attribute to the `src` view attribute (and vice versa).
        editor.conversion.attributeToAttribute({model: 'source', view: 'src'});

        // Attribute values are strictly specified.
        editor.conversion.attributeToAttribute({
            model: {
                name: 'image',
                key: 'aside',
                values: ['aside']
            },
            view: {
                aside: {
                    name: 'imga',
                    key: 'class',
                    value: ['aside', 'half-size']
                }
            }
        });

    }


    // return;

    $('#editor').each(function(index, value) {

        // console.log($(this));

        var classicEditor = CKEditor.ClassicEditor;

        classicEditor
            .create(this, {

                // plugins: [ Title, ],
                // title: {
                //     placeholder: 'My custom placeholder for the title'
                // },
                // placeholder: 'My custom placeholder for the body',

                extraPlugins: [  AllowClassesPlugin2, AllowSourcePlugin ],
                toolbar: [  'bold', 'italic', 'link' ],

                link: {
                    addTargetToExternalLinks: true
                }

            })
            .then(newEditor => {
                editor = newEditor;

                CKEditorInspector.attach( editor );

                // console.log(editor);

                // editor.model.schema.extend( 'table', {
                //     allowAttributes: 'class'
                // } );

/*
                editor.conversion.attributeToAttribute( {
                    model: {
                        name: 'table',
                        key: 'class',
                        values: [ 'big', 'small' ]
                    },
                    view: {
                        big: {
                            name: 'figure',
                            key: 'class',
                            value: [ 'table', 'some-big-table' ]
                        },

                        small: {
                            name: 'figure',
                            key: 'class',
                            value: [ 'table', 'some-big-small' ]
                        }
                    }
                } );
*/

                // console.log(editor);

            })
            .catch(error => {
                console.error(error);
            });


        // console.log(editor);


        // console.log(editor);
    });


    // InlineEditor
    //     .create($('.ck-content').get(0), {
    //         toolbar: [  'bold', 'italic', 'link' ]
    //
    //     })
    //     .then(newEditor => {
    //         editor = newEditor;
    //     })
    //     .catch(error => {
    //         console.error(error);
    //     });



    $('#submit').click(function (e) {
        const editorData = editor.getData();

        console.log(editorData);

    });


    return;



    BalloonEditor
        .create( $('.ck-content').get(0), {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'link' ]
        })
        .then(editor => {
            window.editor = editor;
        })
        .catch(err => {
            console.error(err.stack);
        });



    return;

    CKEDITOR.on("instanceReady", function(ev) {
        // alert('Editor instance ready');
        return true;
    });


    // Sample: Massive Inline Editing

    // This code is generally not necessary, but it is here to demonstrate
    // how to customize specific editor instances on the fly. This fits this
    // demo well because some editable elements (like headers) may
    // require a smaller number of features.

    // The "instanceCreated" event is fired for every editor instance created.
    CKEDITOR.on( 'instanceCreated', function ( event ) {
        var editor = event.editor,
            element = editor.element;

        // console.log(element);
        var oldValue;

        editor.on( 'change', function ( eve ) {
            var element = this.element;

            var copy = $(element).attr("data-copy");

            if (typeof copy !== typeof undefined && copy !== false) {
                // Element has this attribute
                if ($(copy.length > 0)) {
                    $(copy).val(editor.getData());
                }
            }

        });

        editor.on('focus', function (e) {
            // console.log(editor.getData());
            oldValue = editor.getData();
        });

        editor.on('blur', function (e) {
            // console.log(editor.getData());

            if (editor.getData() != oldValue) {
                var el = e.sender.element.$;
                var id = element.getAttribute('data-translate');
                var domain = element.getAttribute('data-domain');

                var namespace = element.getAttribute('data-namespace');
                var source = element.getAttribute('data-source');

                if (id && domain) {
                    $.ajax({
                        url: edit_translate_signal,
                        data: {'domain': domain, 'translateId': id, 'content': editor.getData()},
                        success: function(response){
                            var selectorAnimationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
                            var effect = 'animated pulse ok';
                            if (response.translate == true) {

                                $(el).addClass(effect).one(selectorAnimationEnd, function() {
                                    $(this).removeClass(effect);
                                });

                            } else {
                                effect = 'animated shake fail';

                                $(el).addClass(effect).one(selectorAnimationEnd, function() {
                                    $(this).removeClass(effect);
                                });
                            }
                        }
                    });

                } else if (namespace && source) {
                    console.log(namespace);
                    console.log(source);

                    var routeEl = $("[data-route]");
                    var routeLength = routeEl.length;
                    var route = routeLength == 1 ? routeEl.data('route') : element.getAttribute('data-route');

                    console.log("route " + route);

                    $.nette.ajax({
                        url: edit_article_signal,
                        data: {'namespace': namespace, 'source': source, 'route': route, 'content': editor.getData()},
                        success: function(response){
                            var selectorAnimationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
                            var effect = 'animated pulse ok';
                            if (response.translate == true) {

                                $(el).addClass(effect).one(selectorAnimationEnd, function() {
                                    $(this).removeClass(effect);
                                });

                            } else {
                                effect = 'animated shake fail';

                                $(el).addClass(effect).one(selectorAnimationEnd, function() {
                                    $(this).removeClass(effect);
                                });
                            }
                        }
                    });



                } else {
                    console.log("for translate not defined id [" + id + "] or domain [" + domain + "]");
                    console.log("for article not defined namespace [" + namespace + "] or source [" + source + "]");
                }
            }
        });



        editor.addCommand("mySimpleCommand", { // create named command
            exec: function(edt) {

                var id = element.getAttribute('data-translate');
                var domain = element.getAttribute('data-domain');

                $.ajax({
                    url: edit_translate_signal,
                    data: {'domain': domain, 'translateId': id, 'content': editor.getData()},
                    success: function(handle){
                        console.log(handle);
                    }
                });

                // console.log(element);
//                alert(edt.getData());
            }
        });


        // Customize editors for headers and tag list.
        // These editors do not need features like smileys, templates, iframes etc.
        if ( element.is( 'h1', 'h2', 'h3' ) || element.getAttribute( 'id' ) == 'taglist' ) {
            // Customize the editor configuration on "configLoaded" event,
            // which is fired after the configuration file loading and
            // execution. This makes it possible to change the
            // configuration before the editor initialization takes place.
            editor.on( 'configLoaded', function () {

                // Remove redundant plugins to make the editor simpler.
                // editor.config.removePlugins = 'colorbutton,find,flash,font,' +
                //     'forms,iframe,image,newpage,removeformat,' +
                //     'smiley,specialchar,stylescombo,templates';

                editor.config.removePlugins = 'image';

                editor.config.format_tags = 'h1;h2;h3;h4;h5;h6;section';
                editor.config.format_small={element:"small"};

                // Rearrange the toolbar layout.
                editor.config.toolbarGroups = [
                    { name: 'editing', groups: [ 'basicstyles', 'links' ] },
                    // { name: 'styles', groups: ['Styles', 'Format', 'Font', 'FontSize'] },
                    { name: 'undo' },
                    { name: 'styles' },
//                    { name: 'clipboard', groups: [ 'selection', 'clipboard' ] },
                    { name: 'insert' }
//                    { name: 'about' }
                ];
            } );

        } else {
            editor.on( 'configLoaded', function () {

                // Remove redundant plugins to make the editor simpler.
                editor.config.removePlugins = 'colorbutton,find,flash,' +
                    'forms,iframe,image,newpage,removeformat';

                // Rearrange the toolbar layout.
                editor.config.toolbarGroups = [
                    { name: 'editing', groups: [ 'basicstyles', 'links' , 'stylescombo', 'removeformat' ] },
                    { name: 'undo' },
                    { name: 'styles' },
                    { name: 'insert' }
                    // { name: 'clipboard', groups: [ 'selection', 'clipboard' ] },
                ];
            } );
        }


        // editor.ui.addButton('SuperButton', { // add new button and bind our command
        //     label: "Click me",
        //     command: 'mySimpleCommand',
        //     toolbar: 'insert',
        //     icon: 'https://avatars1.githubusercontent.com/u/5500999?v=2&s=16'
        // });



    } );


})(jQuery);



