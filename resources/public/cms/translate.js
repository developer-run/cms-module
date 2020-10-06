let editor;
var saveCount = 0;


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


function ConvertDivAttributes( editor ) {
    // Allow <div> elements in the model.
    editor.model.schema.register( 'div', {
        allowWhere: '$block',
        // allowContentOf: '$text',
        allowContentOf: '$block',
        // allowContentOf: '$root',

        isLimit: true,

        // Behaves like a self-contained object (e.g. an image).
        isObject: true,

    } );

    const schema = editor.model.schema;


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



$('[data-translate][data-domain]:not(.ck-editor__editable)').click(function (e) {
    if ($(this).hasClass('ck-editor__editable')) {
        return
    }

    function saveData( el, data ) {
        const HTTP_SERVER_LAG = 500;
        //	let count = 0;

        return new Promise( resolve => {
            setTimeout( () => {
                console.log( 'Saved', data, el );


                // var el = e.sender.element.$;
                var id = el.getAttribute('data-translate');
                var domain = el.getAttribute('data-domain');

                console.log(id);
                console.log(domain);

                // return;
                if (id && domain) {
                    $.ajax({
                        url: edit_translate_signal,
                        data: {'domain': domain, 'translateId': id, 'content': editor.getData()},
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
                    console.warn("for translate not defined id [" + id + "] or domain [" + domain + "]");
                }

                resolve();

            }, HTTP_SERVER_LAG );
        } );
    }

    console.log(this);



    var balloonEditor = CKEditor.BalloonEditor;

    balloonEditor
        .create($(this).get(0), {
            // plugins: [ Essentials, Paragraph, Heading, List, Bold, Italic, Placeholder ],
            // plugins: [ Placeholder ],


            // extraPlugins: [ ConvertDivAttributes, AddClassToAllLinks ],
            // extraPlugins: [ AddClassToAllLinks ],
            toolbar: [  'bold', 'italic', 'link' ],

            // toolbar: [ 'heading', '|', 'bold', 'italic', '|' ],
            // items: [ 'heading', '|', 'bold', 'italic', '|', 'fontSize' ],

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


            // extraPlugins: [ ConvertDivAttributes, HandleFontSizeValue ],


        })
        .then(newEditor => {
            editor = newEditor;

            // console.log( editor.getData( editor.view ) );
            // console.log( editor.data.get( { rootName: 'customRoot' } ) );
            // console.log( editor.model );
            // console.log(newEditor);

            const sel = editor.model.document.selection;

            // console.log(sel);
            // console.log( editor.model.getSelectedContent(sel) );


            // editor.execute( 'insertTranslateBox' );


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
