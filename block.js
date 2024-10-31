var el = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType,
	ServerSideRender = wp.components.ServerSideRender,
	TextControl = wp.components.TextControl,
	RadioControl = wp.components.RadioControl,
    SelectControl = wp.components.SelectControl,
	TextareaControl = wp.components.TextareaControl,
	CheckboxControl = wp.components.CheckboxControl,
	InspectorControls = wp.editor.InspectorControls;

registerBlockType( 'lrc/block', {
	title: 'Loan Calculator',
    description: 'Displays the sliders',
	icon: 'admin-settings',
	category: 'widgets',
    edit: function( props ) {		
        return [
            el( 'h2', // Tag type.
               {
                className: props.className,
                },
				'Loan calculator ' + props.attributes.calculator
              ),
            el( InspectorControls, {},
               el( SelectControl, {
                'type':'number',
				'label':'Calculator Number:',
				'value':props.attributes.calculator,
				'options': [
				    {'label':'One','value':'one'},
                    {'label':'Two','value':'two'},
				    {'label':'Three','value':'three'},
				    {'label':'Four','value':'four'},
                    {'label':'Five','value':'five'},
                    {'label':'Six','value':'six'},
                    {'label':'Seven','value':'seven'},
                    {'label':'Eight','value':'eight'},
                    {'label':'Nine','value':'nine'},
                    {'label':'Ten','value':'ten'},
                ],
				onChange: ( option ) => { props.setAttributes( { calculator: option } ); }
					}
                  ),
               ),
		];
	},

	// We're going to be rendering in PHP, so save() can just return null.
	save: function() {
		return null;
	},
} );