( function( blocks, element, components, blockEditor, i18n ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;

	blocks.registerBlockType( 'ultimate-twitter-feeds/feed', {
		apiVersion: 2,
		title: __( 'Ultimate X Feed', 'ultimate-twitter-feeds' ),
		description: __( 'Display an X profile, list, or single post embed.', 'ultimate-twitter-feeds' ),
		icon: 'twitter',
		category: 'widgets',
		attributes: {
			title: { type: 'string', default: 'Ultimate Twitter Feed' },
			feed_type: { type: 'string', default: 'profile' },
			handle: { type: 'string', default: 'TwitterDev' },
			feed_width: { type: 'number', default: 350 },
			feed_height: { type: 'number', default: 600 },
			feed_theme: { type: 'string', default: 'light' },
			feed_lang: { type: 'string', default: '' },
			feed_track: { type: 'boolean', default: false }
		},
		edit: function( props ) {
			var attrs = props.attributes;
			var blockProps = useBlockProps ? useBlockProps( {
				className: 'utfeed-block-editor'
			} ) : {};
			var renderFields = function() {
				return [
				el( TextControl, {
					label: __( 'Title', 'ultimate-twitter-feeds' ),
					value: attrs.title || '',
					onChange: function( value ) { props.setAttributes( { title: value } ); }
				} ),
				el( SelectControl, {
					label: __( 'Feed Type', 'ultimate-twitter-feeds' ),
					value: attrs.feed_type,
					options: [
						{ label: __( 'Profile', 'ultimate-twitter-feeds' ), value: 'profile' },
						{ label: __( 'List', 'ultimate-twitter-feeds' ), value: 'list' },
						{ label: __( 'Single Tweet', 'ultimate-twitter-feeds' ), value: 'single_tweet' }
					],
					onChange: function( value ) { props.setAttributes( { feed_type: value } ); }
				} ),
				el( TextControl, {
					label: __( 'Handle / URL', 'ultimate-twitter-feeds' ),
					value: attrs.handle || '',
					onChange: function( value ) { props.setAttributes( { handle: value } ); }
				} ),
				el( TextControl, {
					label: __( 'Width', 'ultimate-twitter-feeds' ),
					type: 'number',
					value: attrs.feed_width,
					onChange: function( value ) { props.setAttributes( { feed_width: parseInt( value || 0, 10 ) || 350 } ); }
				} ),
				el( TextControl, {
					label: __( 'Height', 'ultimate-twitter-feeds' ),
					type: 'number',
					value: attrs.feed_height,
					onChange: function( value ) { props.setAttributes( { feed_height: parseInt( value || 0, 10 ) || 600 } ); }
				} ),
				el( SelectControl, {
					label: __( 'Theme', 'ultimate-twitter-feeds' ),
					value: attrs.feed_theme,
					options: [
						{ label: __( 'Light', 'ultimate-twitter-feeds' ), value: 'light' },
						{ label: __( 'Dark', 'ultimate-twitter-feeds' ), value: 'dark' }
					],
					onChange: function( value ) { props.setAttributes( { feed_theme: value } ); }
				} ),
				el( TextControl, {
					label: __( 'Language Code', 'ultimate-twitter-feeds' ),
					help: __( 'Use blank for automatic or values like en, es, fr.', 'ultimate-twitter-feeds' ),
					value: attrs.feed_lang || '',
					onChange: function( value ) { props.setAttributes( { feed_lang: value } ); }
				} ),
				el( ToggleControl, {
					label: __( 'Opt-out of tailored content', 'ultimate-twitter-feeds' ),
					checked: !! attrs.feed_track,
					onChange: function( value ) { props.setAttributes( { feed_track: !! value } ); }
				} )
				];
			};

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Feed Settings', 'ultimate-twitter-feeds' ), initialOpen: true },
						renderFields()
					)
				),
				el(
					'div',
					blockProps,
					el( 'strong', null, attrs.title || __( 'Ultimate X Feed', 'ultimate-twitter-feeds' ) ),
					el( 'p', { style: { marginTop: '8px', marginBottom: '12px' } }, __( 'Edit settings below or in the block sidebar.', 'ultimate-twitter-feeds' ) ),
					el(
						'div',
						{
							style: {
								padding: '16px',
								border: '1px solid #ddd',
								borderRadius: '4px',
								background: '#fff'
							}
						},
						renderFields()
					),
					el( 'p', { style: { marginTop: '12px', marginBottom: 0 } }, __( 'Current handle:', 'ultimate-twitter-feeds' ) + ' ' + ( attrs.handle || 'TwitterDev' ) )
				)
			);
		},
		save: function() {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n );
