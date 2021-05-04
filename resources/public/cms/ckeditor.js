// import CKEditorInspector from '/../node_modules/@ckeditor/ckeditor5-inspector/build/inspector.js';
// import InlineEditor from '@ckeditor/ckeditor5-build-inline';
// import ClassicEditor from '@ckeditor/ckeditor5-build-inline';

// import CKEditorInspector from '@ckeditor/ckeditor5-inspector';
// import CKEditorInspector from '../../ckeditorModule/inspector/build/inspector.js';

// import ClassicEditor from '../../ckeditorModule/inline/ckeditor';



// const ClassicEditor = require( '@ckeditor/ckeditor5-build-classic' );

function ConvertDivAttributes( editor ) {
    // Allow <div> elements in the model.
    editor.model.schema.register( 'div', {
        allowWhere: '$block',
        // allowContentOf: '$text',
        // allowContentOf: '$block',
        allowContentOf: '$root',
        // isLimit: true,
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
        model: ( viewElement, { writer: modelWriter } ) => {
            return modelWriter.createElement( 'div', viewElement.getAttributes() );
        },
        converterPriority: 'low'
    } );

    // Model-to-view converter for the <div> element (attributes are converted separately).
    editor.conversion.for( 'downcast' ).elementToElement( {
        model: 'div',
        view: 'div',
        converterPriority: 'low'
    } );

    // Model-to-view converter for <div> attributes.
    // Note that a lower-level, event-based API is used here.
    editor.conversion.for( 'downcast' ).add( dispatcher => {
        dispatcher.on( 'attribute', ( evt, data, conversionApi ) => {
            // Convert <div> attributes only.
            if ( data.item.name !== 'div' ) {
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


function ConvertSectionAttributes( editor ) {
    // Allow <div> elements in the model.
    editor.model.schema.register( 'section', {
        allowWhere: '$block',
        // allowContentOf: '$text',
        // allowContentOf: '$block',
        allowContentOf: '$root',
        // isLimit: true,
    } );

    // Allow <div> elements in the model to have all attributes.
    editor.model.schema.addAttributeCheck( context => {
        return true;
        if ( context.endsWith( 'section' ) ) {
        }
    } );

    // View-to-model converter converting a view <section> with all its attributes to the model.
    editor.conversion.for( 'upcast' ).elementToElement( {
        view: 'section',
        model: ( viewElement, { writer: modelWriter } ) => {
            return modelWriter.createElement( 'section', viewElement.getAttributes() );
        }
    } );

    // Model-to-view converter for the <section> element (attributes are converted separately).
    editor.conversion.for( 'downcast' ).elementToElement( {
        model: 'section',
        view: 'section'
    } );

    // Model-to-view converter for <section> attributes.
    // Note that a lower-level, event-based API is used here.
    editor.conversion.for( 'downcast' ).add( dispatcher => {
        dispatcher.on( 'attribute', ( evt, data, conversionApi ) => {
            // Convert <section> attributes only.
            if ( data.item.name !== 'section' ) {
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

        // console.log(editor);

    }
}


function AllowClassesPlugin(editor) {
    // editor.model.schema.extend( 'table', {
    //     allowAttributes: 'class'
    // } );

    editor.conversion.attributeToAttribute( {
        model: {
            name: 'tablea',
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

function HandleFontSizeValue( editor ) {
    // Add a special catch-all converter for the font size feature.
    // z view do modelu
    editor.conversion.for( 'upcast' ).elementToAttribute( {
        view: {
            name: 'span',
            styles: {
                'font-size': /[\s\S]+/
            }
        },
        model: {
            key: 'fontSize',
            value: viewElement => {
                const value = parseFloat( viewElement.getStyle( 'font-size' ) ).toFixed( 0 );

                // It might be needed to further convert the value to meet business requirements.
                // In the sample the font size is configured to handle only the sizes:
                // 12, 14, 'default', 18, 20, 22, 24, 26, 28, 30
                // Other sizes will be converted to the model but the UI might not be aware of them.

                // The font size feature expects numeric values to be Number, not String.
                return parseInt( value );
            }
        },
        converterPriority: 'high'
    } );

    // Add a special converter for the font size feature to convert all (even not configured)
    // model attribute values.
    // z modelu do viewu
    editor.conversion.for( 'downcast' ).attributeToElement( {
        model: {
            key: 'fontSize'
        },
        view: ( modelValue, { writer: viewWriter } ) => {
            return viewWriter.createAttributeElement( 'span', {
                style: `font-size:${ modelValue }px`
            } );
        },
        converterPriority: 'high'
    } );
}




let editor;


// let qw = ClassicEditor.builtinPlugins.map( plugin => plugin.pluginName );

// console.log(qw);

class Placeholder extends Plugin {
    static get requires() {
        return [ PlaceholderEditing, PlaceholderUI ];
    }
}

/*
class PlaceholderCommand extends Command {
    execute( { value } ) {
        const editor = this.editor;

        editor.model.change( writer => {
            // Create a <placeholder> elment with the "name" attribute...
            const placeholder = writer.createElement( 'placeholder', { name: value } );

            // ... and insert it into the document.
            editor.model.insertContent( placeholder );

            // Put the selection on the inserted element.
            writer.setSelection( placeholder, 'on' );
        } );
    }

    refresh() {
        const model = this.editor.model;
        const selection = model.document.selection;

        const isAllowed = model.schema.checkChild( selection.focus.parent, 'placeholder' );

        this.isEnabled = isAllowed;
    }
}

class PlaceholderUI extends Plugin {
    init() {
        const editor = this.editor;
        const t = editor.t;
        const placeholderNames = editor.config.get( 'placeholderConfig.types' );

        // The "placeholder" dropdown must be registered among the UI components of the editor
        // to be displayed in the toolbar.
        editor.ui.componentFactory.add( 'placeholder', locale => {
            const dropdownView = createDropdown( locale );

            // Populate the list in the dropdown with items.
            addListToDropdown( dropdownView, getDropdownItemsDefinitions( placeholderNames ) );

            dropdownView.buttonView.set( {
                // The t() function helps localize the editor. All strings enclosed in t() can be
                // translated and change when the language of the editor changes.
                label: t( 'Placeholder' ),
                tooltip: true,
                withText: true
            } );

            // Disable the placeholder button when the command is disabled.
            const command = editor.commands.get( 'placeholder' );
            dropdownView.bind( 'isEnabled' ).to( command );

            // Execute the command when the dropdown item is clicked (executed).
            this.listenTo( dropdownView, 'execute', evt => {
                editor.execute( 'placeholder', { value: evt.source.commandParam } );
                editor.editing.view.focus();
            } );

            return dropdownView;
        } );
    }
}

function getDropdownItemsDefinitions( placeholderNames ) {
    const itemDefinitions = new Collection();

    for ( const name of placeholderNames ) {
        const definition = {
            type: 'button',
            model: new Model( {
                commandParam: name,
                label: name,
                withText: true
            } )
        };

        // Add the item definition to the collection.
        itemDefinitions.add( definition );
    }

    return itemDefinitions;
}

class PlaceholderEditing extends Plugin {
    static get requires() {
        return [ Widget ];
    }

    init() {
        console.log( 'PlaceholderEditing#init() got called' );

        this._defineSchema();
        this._defineConverters();

        this.editor.commands.add( 'placeholder', new PlaceholderCommand( this.editor ) );

        this.editor.editing.mapper.on(
            'viewToModelPosition',
            viewToModelPositionOutsideModelElement( this.editor.model, viewElement => viewElement.hasClass( 'placeholder' ) )
        );
        this.editor.config.define( 'placeholderConfig', {
            types: [ 'date', 'first name', 'surname' ]
        } );
    }

    _defineSchema() {
        const schema = this.editor.model.schema;

        schema.register( 'placeholder', {
            // Allow wherever text is allowed:
            allowWhere: '$text',

            // The placeholder will act as an inline node:
            isInline: true,

            // The inline widget is self-contained so it cannot be split by the caret and it can be selected:
            isObject: true,

            // The placeholder can have many types, like date, name, surname, etc:
            allowAttributes: [ 'name' ]
        } );
    }

    _defineConverters() {
        const conversion = this.editor.conversion;

        conversion.for( 'upcast' ).elementToElement( {
            view: {
                name: 'span',
                classes: [ 'placeholder' ]
            },
            model: ( viewElement, modelWriter ) => {
                // Extract the "name" from "{name}".
                const name = viewElement.getChild( 0 ).data.slice( 1, -1 );

                return modelWriter.createElement( 'placeholder', { name } );
            }
        } );

        conversion.for( 'editingDowncast' ).elementToElement( {
            model: 'placeholder',
            view: ( modelItem, viewWriter ) => {
                const widgetElement = createPlaceholderView( modelItem, viewWriter );

                // Enable widget handling on a placeholder element inside the editing view.
                return toWidget( widgetElement, viewWriter );
            }
        } );

        conversion.for( 'dataDowncast' ).elementToElement( {
            model: 'placeholder',
            view: createPlaceholderView
        } );

        // Helper method for both downcast converters.
        function createPlaceholderView( modelItem, viewWriter ) {
            const name = modelItem.getAttribute( 'name' );

            const placeholderView = viewWriter.createContainerElement( 'span', {
                class: 'placeholder'
            } );

            // Insert the placeholder name (as a text).
            const innerText = viewWriter.createText( '{' + name + '}' );
            viewWriter.insert( viewWriter.createPositionAt( placeholderView, 0 ), innerText );

            return placeholderView;
        }
    }
}

*/



function Moje(editor) {

    let allowedAttributes = [
        'id',
        'class'
    ];

    editor.model.schema.extend('$root', { allowAttributes: allowedAttributes });
    editor.model.schema.extend('$block', { allowAttributes: allowedAttributes });
    editor.model.schema.extend('$text', { allowAttributes: allowedAttributes });

    // A simple conversion from the `source` model attribute to the `src` view attribute (and vice versa).
    editor.conversion.attributeToAttribute({model: 'source', view: 'src'});

    editor.conversion.attributeToAttribute({model: 'class', view: 'class'});


    editor.model.schema.addAttributeCheck( context => {
        return true;
        if ( context.endsWith( 'section' ) ) {
        }
    } );


    // Attribute values are strictly specified.
    editor.conversion.attributeToAttribute({
        model: {
            name: 'image',
            key: 'aside',
            values: ['aside']
        },
        view: {
            aside: {
                name: 'img',
                key: 'class',
                value: ['aside', 'half-size']
            }
        }
    });

}

// This plugin brings customization to the downcast pipeline of the editor.
function AddClassToAllLinks( editor ) {

    // const schema = editor.model.schema;
    // schema.allowWhere = "$text";
    // schema.allowContentOf = "$text";
    // schema.isLimit = true;
    // schema.isObject = true;

    editor.model.schema.extend( '$text', { allowAttributes: 'linkTarget' } );
    editor.model.schema.extend( '$text', { allowAttributes: 'paragraph' } );

    // console.log(schema);

    // Both the data and the editing pipelines are affected by this conversion.
    editor.conversion.for( 'downcast' ).add( dispatcher => {
        // Links are represented in the model as a "linkHref" attribute.
        // Use the "low" listener priority to apply the changes after the link feature.
        dispatcher.on( 'attribute:linkHref', ( evt, data, conversionApi ) => {
            const viewWriter = conversionApi.writer;
            const viewSelection = viewWriter.document.selection;

            // Adding a new CSS class is done by wrapping all link ranges and selection
            // in a new attribute element with a class.
            const viewElement = viewWriter.createAttributeElement( 'a', {
                class: 'my-green-link'
            }, {
                priority: 5
            } );

            if ( data.item.is( 'selection' ) ) {
                viewWriter.wrap( viewSelection.getFirstRange(), viewElement );
            } else {
                viewWriter.wrap( conversionApi.mapper.toViewRange( data.range ), viewElement );
            }
        }, { priority: 'low' } );
    } );
}


$("h2").click(function (e) {

    return;
    editor.destroy();
    console.log("ničíme");

});


$("#editor1").click(function (e) {

    console.log(this);
    return;

    CKEditor.ClassicEditor
        .create($("#editor1").get(0), {
            // plugins: [ Essentials, Paragraph, Heading, List, Bold, Italic, Placeholder ],
            // plugins: [ Placeholder ],


            // extraPlugins: [ HandleFontSizeValue,  AllowSourcePlugin ],
            // toolbar: [  'bold', 'italic', 'link' ]

            toolbar: [ 'heading', '|', 'bold', 'italic', '|', 'fontSize' ],
            // items: [ 'heading', '|', 'bold', 'italic', '|', 'fontSize' ],
            fontSize: {
                options: [ 10, 12, 14, 'default', 18, 20, 22 ]
            },
            extraPlugins: [ ConvertDivAttributes, HandleFontSizeValue ],

        })
        .then(newEditor => {
            editor = newEditor;

            console.log( editor.getData( editor.view ) );
            // console.log( editor.data.get( { rootName: 'customRoot' } ) );
            console.log( editor.model );
            console.log(newEditor);

            const sel = editor.model.document.selection;

            console.log(sel);
            console.log( editor.model.getSelectedContent(sel) );




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





});



$('.editor').each(function(index, value) {

    // let editor;
    var saveCount = 0;

    var config = {
        // plugins: [ Essentials, Paragraph, Heading, List, Bold, Italic, Placeholder ],
        // plugins: [ Placeholder ],

        // toolbar: toolbar,

        // extraPlugins: [ HandleFontSizeValue,  AllowSourcePlugin ],
        // toolbar: [  'bold', 'italic', 'link', '|' ],
        // toolbar: [ 'heading', '|', 'bold', 'italic', '|', 'fontSize' ],
        // toolbar: [ 'heading', '|', 'bold', 'italic', 'link', '|', 'fontSize' ],
        // items: [ 'heading', '|', 'bold', 'italic', '|', 'fontSize' ],
        fontSize: {
            options: [ 10, 12, 14, 'default', 18, 20, 22 ]
        },
        // extraPlugins: [ ConvertDivAttributes, HandleFontSizeValue ],

    };


    config = {
        extraPlugins: [ConvertDivAttributes, ConvertSectionAttributes, HandleFontSizeValue, Moje],
        // extraPlugins: [ ConvertDivAttributes, HandleFontSizeValue ],

        // plugins: ['Heading', 'Control', 'RowGrid'],
        // toolbar: [ 'heading', 'bold', 'italic', 'alignment', 'numberedList', 'bulletedList', '|', 'placeholder', 'translateBox' , 'control', 'rowGrid' ],


        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' }
            ]
        },

        autosave: {
            save( editor ) {
                // The saveData() function must return a promise
                // which should be resolved when the data is successfully saved.

                if ( saveCount === 0 ) {
                    saveCount++;
                    return new Promise( resolve => {
                        resolve();
                    } );
                }

                return saveData( editor.sourceElement, editor.getData() );
            }
        },
    };

    var language = $("html").attr('lang');
    if (language) config['language'] = language;

    // console.log($(this).get(0));
    // console.log($(this));

    var toolbarEl = $(this).data('toolbar');
    var toolbarEl = $(this).data('options');
    if (toolbarEl) {
//        console.log(toolbarEl);
        // toolbarEl = JSON.parse(toolbarEl);
    }

    if (toolbarEl && (typeof toolbarEl === "object")) {
        $.extend(config, toolbarEl);
    }

    console.log(toolbarEl);
    console.log(config);

    function saveData( el, data ) {
        const HTTP_SERVER_LAG = 500;
        //	let count = 0;

        return new Promise( resolve => {
            setTimeout( () => {
                console.log( 'Saved', data, el );

                var pageEL = $("#page");
                var namespace = el.getAttribute('data-namespace');
                var source = el.getAttribute('data-source');
                var type = el.getAttribute('data-content-type');

                var routeID = pageEL.data('route');
                var packageID = pageEL.data('package');
                var pageID = pageEL.data('page');
                var usedPage = $(el).data('page'), usedPackage = $(el).data('package'), usedRoute = $(el).data('route');
                var params = {};

                if (usedPage) params.page = true;
                if (usedPackage) params.package = true;
                if (usedRoute) params.route = true;


                // console.log(params);

                // console.log(routeID);
                // console.log(usedPage);
                // console.log(usedPackage);
                // console.log(usedRoute);

                // console.log(namespace);
                // console.log(source);
                // console.log(edit_article_signal);

                if (namespace && source) {
                    $.ajax({
                        url: edit_article_signal,
                        data: {'namespace': namespace, 'source': source, 'type': type, 'pageId': pageID, 'packageId': packageID, 'routeId': routeID, params: params, 'content': data},
                        success: function(response){
                            var selectorAnimationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
                            var effect = 'animated pulse ok';
                            if (response.translate === true) {

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
                    console.warn("for article not defined namespace [" + namespace + "] or source [" + source + "]");
                }

                resolve();

            }, HTTP_SERVER_LAG );
        } );
    }




    CKEditor.InlineEditor
        .create($(this).get(0), config)
        .then(newEditor => {
            editor = newEditor;

            // console.log( editor.getData( editor.view ) );
            // console.log( editor.data.get( { rootName: 'customRoot' } ) );
            // console.log( editor.model );
            // console.log(newEditor.toolbar);

            const sel = editor.model.document.selection;

            // console.log(sel);
            // console.log( editor.model.getSelectedContent(sel) );




            // CKEditorInspector.attach( editor );




        })
        .catch(error => {
            console.error(error);
        });



    // console.log(editor);


    // console.log(editor);
});
