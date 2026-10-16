( function ( blocks, blockEditor, components, data, element, i18n ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var registerBlockType = blocks.registerBlockType;
	var createBlock = blocks.createBlock;
	var useBlockProps = blockEditor.useBlockProps;
	var InnerBlocks = blockEditor.InnerBlocks;
	var InspectorControls = blockEditor.InspectorControls;
	var MediaUpload = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;
	var RichText = blockEditor.RichText;
	var Button = components.Button;
	var PanelBody = components.PanelBody;
	var Placeholder = components.Placeholder;
	var RangeControl = components.RangeControl;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var TextareaControl = components.TextareaControl;
	var __ = i18n.__;

	var SPACE_BLOCKS = [
		'kilka-exhibitions/image-space',
		'kilka-exhibitions/text-space',
		'kilka-exhibitions/pause-space'
	];

	var intervalOptions = [
		{ label: __( 'Short', 'kilka-exhibitions' ), value: 'short' },
		{ label: __( 'Normal', 'kilka-exhibitions' ), value: 'normal' },
		{ label: __( 'Long', 'kilka-exhibitions' ), value: 'long' }
	];

	var presenceValues = [ 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100 ];
	var legacyPresence = {
		small: 40,
		half: 50,
		medium: 60,
		large: 80,
		immersive: 100
	};
	var presenceHeightLimits = {
		35: 48,
		40: 52,
		45: 56,
		50: 60,
		55: 64,
		60: 68,
		65: 72,
		70: 76,
		75: 80,
		80: 84,
		85: 87,
		90: 90,
		95: 92,
		100: 94
	};

	function preset( value, allowed, fallback ) {
		return allowed.indexOf( value ) === -1 ? fallback : value;
	}

	function imagePresence( scale ) {
		var numericPresence;

		if ( legacyPresence[ scale ] ) {
			return legacyPresence[ scale ];
		}

		numericPresence = parseInt( String( scale ).replace( 'p', '' ), 10 );

		return presenceValues.indexOf( numericPresence ) === -1 ? 60 : numericPresence;
	}

	function imageScaleClass( scale ) {
		var legacyScales = Object.keys( legacyPresence );
		var presenceScales = presenceValues.map( function ( value ) {
			return 'p' + value;
		} );

		return preset( scale, legacyScales.concat( presenceScales ), 'medium' );
	}

	function imageLimitStyle( attributes ) {
		var width = parseInt( attributes.mediaWidth, 10 );
		var height = parseInt( attributes.mediaHeight, 10 );
		var presence = imagePresence( imageScaleClass( attributes.scale ) );
		var heightLimit = presenceHeightLimits[ presence ];
		var ratio;

		if ( ! width || ! height ) {
			return {};
		}

		ratio = width / height;

		return {
			'--kilka-exhibition-image-height-width': ( heightLimit * ratio ).toFixed( 2 ) + 'vh',
			'--kilka-exhibition-image-mobile-height-width': ( Math.min( heightLimit, 82 ) * ratio ).toFixed( 2 ) + 'vh'
		};
	}

	function mediaText( value ) {
		var container;

		if ( value && 'object' === typeof value ) {
			value = value.raw || value.rendered || '';
		}

		if ( ! value ) {
			return '';
		}

		container = document.createElement( 'div' );
		container.innerHTML = value;

		return ( container.textContent || container.innerText || '' ).trim();
	}

	function spaceClassName( type, attributes ) {
		var classNames = [ 'kilka-exhibition-space', 'kilka-exhibition-' + type + '-space' ];

		if ( attributes.interval ) {
			classNames.push(
				'has-' + preset( attributes.interval, [ 'short', 'normal', 'long' ], 'normal' ) + '-interval'
			);
		}

		return classNames.join( ' ' );
	}

	function textSpaceClassName( attributes ) {
		var width = preset( attributes.width, [ 'compact', 'narrow', 'standard', 'wide', 'full' ], 'narrow' );
		var alignment = preset( attributes.alignment, [ 'left', 'center' ], 'left' );
		var placement = preset( attributes.placement, [ 'left', 'center', 'right' ], 'center' );
		var textScale = preset( attributes.textScale, [ 'small', 'normal', 'large', 'statement' ], 'normal' );
		var height = preset( attributes.height, [ 'content', 'half', 'viewport' ], 'content' );
		var verticalPlacement = preset( attributes.verticalPlacement, [ 'top', 'center', 'bottom' ], 'center' );
		var marker = preset( attributes.marker, [ 'none', 'short-line' ], 'none' );
		var className = spaceClassName( 'text', attributes ) + ' is-width-' + width + ' is-aligned-' + alignment;

		// Centre is the default and is omitted to preserve existing saved markup.
		if ( 'center' !== placement ) {
			className += ' is-placed-' + placement;
		}

		// New structural defaults are omitted so version 0.2.0 blocks stay valid.
		if ( 'normal' !== textScale ) {
			className += ' is-text-' + textScale;
		}

		if ( 'content' !== height ) {
			className += ' is-height-' + height;
		}

		if ( 'center' !== verticalPlacement ) {
			className += ' is-vertical-' + verticalPlacement;
		}

		if ( 'none' !== marker ) {
			className += ' has-marker-' + marker;
		}

		return className;
	}

	registerBlockType( 'kilka-exhibitions/sequence', {
		apiVersion: 2,
		title: __( 'Exhibition Sequence', 'kilka-exhibitions' ),
		description: __( 'Contains the ordered spaces that form an exhibition.', 'kilka-exhibitions' ),
		category: 'kilka-exhibitions',
		icon: 'format-gallery',
		supports: {
			html: false,
			multiple: false,
			reusable: false
		},
		edit: function ( props ) {
			var blockProps = useBlockProps( {
				className: 'kilka-exhibition-sequence-editor'
			} );

			function addSpace( blockName ) {
				data.dispatch( 'core/block-editor' ).insertBlocks(
					createBlock( blockName ),
					undefined,
					props.clientId
				);
			}

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ className: 'kilka-exhibition-sequence-editor__heading' },
					el( 'strong', null, __( 'Exhibition sequence', 'kilka-exhibitions' ) ),
					el(
						'p',
						null,
						__( 'Add Image, Text, or Pause spaces, then reorder them in the sequence or List View.', 'kilka-exhibitions' )
					)
				),
				el( InnerBlocks, {
					allowedBlocks: SPACE_BLOCKS,
					templateLock: false,
					renderAppender: false
				} ),
				el(
					'div',
					{ className: 'kilka-exhibition-sequence-editor__add-space' },
					el( 'span', null, __( 'Add space', 'kilka-exhibitions' ) ),
					el(
						'div',
						{ className: 'kilka-exhibition-sequence-editor__add-space-buttons' },
						el(
							Button,
							{
								icon: 'format-image',
								isSecondary: true,
								onClick: function () {
									addSpace( 'kilka-exhibitions/image-space' );
								}
							},
							__( 'Image', 'kilka-exhibitions' )
						),
						el(
							Button,
							{
								icon: 'editor-textcolor',
								isSecondary: true,
								onClick: function () {
									addSpace( 'kilka-exhibitions/text-space' );
								}
							},
							__( 'Text', 'kilka-exhibitions' )
						),
						el(
							Button,
							{
								icon: 'minus',
								isSecondary: true,
								onClick: function () {
									addSpace( 'kilka-exhibitions/pause-space' );
								}
							},
							__( 'Pause', 'kilka-exhibitions' )
						)
					)
				)
			);
		},
		save: function () {
			return el(
				'div',
				useBlockProps.save( { className: 'kilka-exhibition-sequence' } ),
				el( InnerBlocks.Content )
			);
		}
	} );

	registerBlockType( 'kilka-exhibitions/image-space', {
		apiVersion: 2,
		title: __( 'Image Space', 'kilka-exhibitions' ),
		description: __( 'Presents one media-library image within the exhibition sequence.', 'kilka-exhibitions' ),
		category: 'kilka-exhibitions',
		parent: [ 'kilka-exhibitions/sequence' ],
		icon: 'format-image',
		attributes: {
			mediaId: { type: 'number', default: 0 },
			mediaUrl: { type: 'string', default: '' },
			mediaAlt: { type: 'string', default: '' },
			mediaWidth: { type: 'number', default: 0 },
			mediaHeight: { type: 'number', default: 0 },
			caption: { type: 'string', default: '' },
			scale: { type: 'string', default: 'medium' },
			alignment: { type: 'string', default: 'center' },
			interval: { type: 'string', default: 'normal' },
			captionMode: { type: 'string', default: 'information' },
			creatorOverride: { type: 'string', default: '' },
			copyrightOverride: { type: 'string', default: '' }
		},
		supports: {
			html: false,
			reusable: false
		},
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var scale = imageScaleClass( attributes.scale );
			var presence = imagePresence( scale );
			var alignment = preset( attributes.alignment, [ 'left', 'center', 'right' ], 'center' );
			var interval = preset( attributes.interval, [ 'short', 'normal', 'long' ], 'normal' );
			var captionMode = preset( attributes.captionMode, [ 'information', 'visible', 'hidden' ], 'information' );
			var blockProps = useBlockProps( {
				className: spaceClassName( 'image', attributes ) + ' is-scale-' + scale + ' is-aligned-' + alignment,
				style: imageLimitStyle( attributes )
			} );

			function selectImage( media ) {
				setAttributes( {
					mediaId: media.id || 0,
					mediaUrl: media.url || '',
					mediaAlt: media.alt || '',
					mediaWidth: media.width || 0,
					mediaHeight: media.height || 0,
					caption: mediaText( media.caption )
				} );
			}

			function clearImage() {
				setAttributes( {
					mediaId: 0,
					mediaUrl: '',
					mediaAlt: '',
					mediaWidth: 0,
					mediaHeight: 0,
					caption: ''
				} );
			}

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Composition', 'kilka-exhibitions' ), initialOpen: true },
						el( RangeControl, {
							label: __( 'Image presence', 'kilka-exhibitions' ),
							help: __( 'Controls the visual presence of the work without exposing pixel dimensions.', 'kilka-exhibitions' ),
							value: presence,
							min: 35,
							max: 100,
							step: 5,
							onChange: function ( value ) {
								setAttributes( { scale: 'p' + ( value || 60 ) } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Alignment', 'kilka-exhibitions' ),
							value: alignment,
							options: [
								{ label: __( 'Left', 'kilka-exhibitions' ), value: 'left' },
								{ label: __( 'Centre', 'kilka-exhibitions' ), value: 'center' },
								{ label: __( 'Right', 'kilka-exhibitions' ), value: 'right' }
							],
							onChange: function ( value ) {
								setAttributes( { alignment: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Interval after image', 'kilka-exhibitions' ),
							value: interval,
							options: intervalOptions,
							onChange: function ( value ) {
								setAttributes( { interval: value } );
							}
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Image information', 'kilka-exhibitions' ), initialOpen: false },
						el( TextareaControl, {
							label: __( 'Alternative text', 'kilka-exhibitions' ),
							help: __( 'Inherited from the media library when selected. Describe the image meaningfully for people who cannot see it.', 'kilka-exhibitions' ),
							value: attributes.mediaAlt,
							onChange: function ( value ) {
								setAttributes( { mediaAlt: value } );
							}
						} ),
						el( TextareaControl, {
							label: __( 'Caption or work description', 'kilka-exhibitions' ),
							value: attributes.caption,
							onChange: function ( value ) {
								setAttributes( { caption: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Caption display', 'kilka-exhibitions' ),
							value: captionMode,
							options: [
								{ label: __( 'Information panel', 'kilka-exhibitions' ), value: 'information' },
								{ label: __( 'Visible below image', 'kilka-exhibitions' ), value: 'visible' },
								{ label: __( 'Hidden', 'kilka-exhibitions' ), value: 'hidden' }
							],
							onChange: function ( value ) {
								setAttributes( { captionMode: value } );
							}
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Rights overrides', 'kilka-exhibitions' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Creator override', 'kilka-exhibitions' ),
							help: __( 'Leave empty to use the exhibition default.', 'kilka-exhibitions' ),
							value: attributes.creatorOverride,
							onChange: function ( value ) {
								setAttributes( { creatorOverride: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Copyright override', 'kilka-exhibitions' ),
							help: __( 'Leave empty to use the exhibition default.', 'kilka-exhibitions' ),
							value: attributes.copyrightOverride,
							onChange: function ( value ) {
								setAttributes( { copyrightOverride: value } );
							}
						} )
					)
				),
				el(
					'figure',
					blockProps,
					attributes.mediaUrl ?
						el(
							Fragment,
							null,
							el( 'img', {
								src: attributes.mediaUrl,
								alt: attributes.mediaAlt,
								width: attributes.mediaWidth || undefined,
								height: attributes.mediaHeight || undefined
							} ),
							! attributes.mediaAlt && el(
								'p',
								{ className: 'kilka-exhibition-editor-warning' },
								__( 'Add alternative text in the block settings.', 'kilka-exhibitions' )
							),
							el(
								'div',
								{ className: 'kilka-exhibition-image-space-editor__actions' },
								el(
									MediaUploadCheck,
									null,
									el( MediaUpload, {
										onSelect: selectImage,
										allowedTypes: [ 'image' ],
										value: attributes.mediaId,
										render: function ( mediaUploadProps ) {
											return el(
												Button,
												{ onClick: mediaUploadProps.open, isSecondary: true },
												__( 'Replace image', 'kilka-exhibitions' )
											);
										}
									} )
								),
								el(
									Button,
									{ onClick: clearImage, isDestructive: true },
									__( 'Remove image', 'kilka-exhibitions' )
								)
							)
						) :
						el(
							MediaUploadCheck,
							null,
							el( MediaUpload, {
								onSelect: selectImage,
								allowedTypes: [ 'image' ],
								value: attributes.mediaId,
								render: function ( mediaUploadProps ) {
									return el(
										Placeholder,
										{
											icon: 'format-image',
											label: __( 'Image Space', 'kilka-exhibitions' ),
											instructions: __( 'Choose one image from the WordPress media library.', 'kilka-exhibitions' )
										},
										el(
											Button,
											{ onClick: mediaUploadProps.open, isPrimary: true },
											__( 'Select image', 'kilka-exhibitions' )
										)
									);
								}
							} )
						)
				)
			);
		},
		save: function ( props ) {
			var attributes = props.attributes;
			var scale = imageScaleClass( attributes.scale );
			var alignment = preset( attributes.alignment, [ 'left', 'center', 'right' ], 'center' );
			var captionMode = preset( attributes.captionMode, [ 'information', 'visible', 'hidden' ], 'information' );
			var captionClass = 'visible' === captionMode ?
				'kilka-exhibition-image-space__caption' :
				'kilka-exhibition-image-space__caption kilka-exhibition-visually-hidden';

			return el(
				'figure',
				useBlockProps.save( {
					className: spaceClassName( 'image', attributes ) + ' is-scale-' + scale + ' is-aligned-' + alignment,
					'data-media-id': attributes.mediaId || undefined,
					'data-caption-mode': captionMode
				} ),
				attributes.mediaUrl && el( 'img', {
					src: attributes.mediaUrl,
					alt: attributes.mediaAlt,
					width: attributes.mediaWidth || undefined,
					height: attributes.mediaHeight || undefined
				} ),
				attributes.caption && 'hidden' !== captionMode && el(
					'figcaption',
					{ className: captionClass },
					attributes.caption
				)
			);
		}
	} );

	registerBlockType( 'kilka-exhibitions/text-space', {
		apiVersion: 2,
		title: __( 'Text Space', 'kilka-exhibitions' ),
		description: __( 'Adds a short curatorial passage or transition.', 'kilka-exhibitions' ),
		category: 'kilka-exhibitions',
		parent: [ 'kilka-exhibitions/sequence' ],
		icon: 'editor-textcolor',
		attributes: {
			content: { type: 'string', source: 'html', selector: 'p', default: '' },
			width: { type: 'string', default: 'narrow' },
			placement: { type: 'string', default: 'center' },
			alignment: { type: 'string', default: 'left' },
			textScale: { type: 'string', default: 'normal' },
			height: { type: 'string', default: 'content' },
			verticalPlacement: { type: 'string', default: 'center' },
			marker: { type: 'string', default: 'none' },
			interval: { type: 'string', default: 'normal' }
		},
		supports: {
			html: false,
			reusable: false
		},
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var width = preset( attributes.width, [ 'compact', 'narrow', 'standard', 'wide', 'full' ], 'narrow' );
			var placement = preset( attributes.placement, [ 'left', 'center', 'right' ], 'center' );
			var alignment = preset( attributes.alignment, [ 'left', 'center' ], 'left' );
			var textScale = preset( attributes.textScale, [ 'small', 'normal', 'large', 'statement' ], 'normal' );
			var height = preset( attributes.height, [ 'content', 'half', 'viewport' ], 'content' );
			var verticalPlacement = preset( attributes.verticalPlacement, [ 'top', 'center', 'bottom' ], 'center' );
			var marker = preset( attributes.marker, [ 'none', 'short-line' ], 'none' );
			var interval = preset( attributes.interval, [ 'short', 'normal', 'long' ], 'normal' );
			var blockProps = useBlockProps( {
				className: textSpaceClassName( attributes )
			} );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Text composition', 'kilka-exhibitions' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Width', 'kilka-exhibitions' ),
							value: width,
							options: [
								{ label: __( 'Compact', 'kilka-exhibitions' ), value: 'compact' },
								{ label: __( 'Narrow', 'kilka-exhibitions' ), value: 'narrow' },
								{ label: __( 'Standard', 'kilka-exhibitions' ), value: 'standard' },
								{ label: __( 'Wide', 'kilka-exhibitions' ), value: 'wide' },
								{ label: __( 'Full width', 'kilka-exhibitions' ), value: 'full' }
							],
							onChange: function ( value ) {
								setAttributes( { width: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Position', 'kilka-exhibitions' ),
							help: __( 'Moves the text block within the full exhibition width.', 'kilka-exhibitions' ),
							value: placement,
							options: [
								{ label: __( 'Left', 'kilka-exhibitions' ), value: 'left' },
								{ label: __( 'Centre', 'kilka-exhibitions' ), value: 'center' },
								{ label: __( 'Right', 'kilka-exhibitions' ), value: 'right' }
							],
							onChange: function ( value ) {
								setAttributes( { placement: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Text alignment', 'kilka-exhibitions' ),
							value: alignment,
							options: [
								{ label: __( 'Left', 'kilka-exhibitions' ), value: 'left' },
								{ label: __( 'Centre', 'kilka-exhibitions' ), value: 'center' }
							],
							onChange: function ( value ) {
								setAttributes( { alignment: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Text size', 'kilka-exhibitions' ),
							value: textScale,
							options: [
								{ label: __( 'Small', 'kilka-exhibitions' ), value: 'small' },
								{ label: __( 'Normal', 'kilka-exhibitions' ), value: 'normal' },
								{ label: __( 'Large', 'kilka-exhibitions' ), value: 'large' },
								{ label: __( 'Statement', 'kilka-exhibitions' ), value: 'statement' }
							],
							onChange: function ( value ) {
								setAttributes( { textScale: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Space height', 'kilka-exhibitions' ),
							help: __( 'Sets a minimum height; longer text can still expand the space.', 'kilka-exhibitions' ),
							value: height,
							options: [
								{ label: __( 'By content', 'kilka-exhibitions' ), value: 'content' },
								{ label: __( 'Half viewport', 'kilka-exhibitions' ), value: 'half' },
								{ label: __( 'Full viewport', 'kilka-exhibitions' ), value: 'viewport' }
							],
							onChange: function ( value ) {
								setAttributes( { height: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Vertical position', 'kilka-exhibitions' ),
							help: 'content' === height ?
								__( 'Choose Half viewport or Full viewport to create vertical space.', 'kilka-exhibitions' ) :
								__( 'Places the text within the selected space height.', 'kilka-exhibitions' ),
							value: verticalPlacement,
							disabled: 'content' === height,
							options: [
								{ label: __( 'Top', 'kilka-exhibitions' ), value: 'top' },
								{ label: __( 'Centre', 'kilka-exhibitions' ), value: 'center' },
								{ label: __( 'Bottom', 'kilka-exhibitions' ), value: 'bottom' }
							],
							onChange: function ( value ) {
								setAttributes( { verticalPlacement: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Text marker', 'kilka-exhibitions' ),
							help: __( 'Adds one restrained visual cue before the text.', 'kilka-exhibitions' ),
							value: marker,
							options: [
								{ label: __( 'None', 'kilka-exhibitions' ), value: 'none' },
								{ label: __( 'Short line', 'kilka-exhibitions' ), value: 'short-line' }
							],
							onChange: function ( value ) {
								setAttributes( { marker: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Interval after text', 'kilka-exhibitions' ),
							value: interval,
							options: intervalOptions,
							onChange: function ( value ) {
								setAttributes( { interval: value } );
							}
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( RichText, {
						tagName: 'p',
						className: 'kilka-exhibition-text-space__input',
						value: attributes.content,
						allowedFormats: [ 'core/bold', 'core/italic', 'core/link' ],
						placeholder: __( 'Write a short curatorial passage or transition…', 'kilka-exhibitions' ),
						onChange: function ( value ) {
							setAttributes( { content: value } );
						}
					} )
				)
			);
		},
		save: function ( props ) {
			var attributes = props.attributes;
			return el(
				'div',
				useBlockProps.save( {
					className: textSpaceClassName( attributes )
				} ),
				el( RichText.Content, { tagName: 'p', value: attributes.content } )
			);
		}
	} );

	registerBlockType( 'kilka-exhibitions/pause-space', {
		apiVersion: 2,
		title: __( 'Pause Space', 'kilka-exhibitions' ),
		description: __( 'Creates an intentional empty interval in the sequence.', 'kilka-exhibitions' ),
		category: 'kilka-exhibitions',
		parent: [ 'kilka-exhibitions/sequence' ],
		icon: 'minus',
		attributes: {
			length: { type: 'string', default: 'normal' }
		},
		supports: {
			html: false,
			reusable: false
		},
		edit: function ( props ) {
			var length = preset( props.attributes.length, [ 'short', 'normal', 'long', 'viewport' ], 'normal' );
			var blockProps = useBlockProps( {
				className: 'kilka-exhibition-space kilka-exhibition-pause-space is-length-' + length
			} );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Pause', 'kilka-exhibitions' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Length', 'kilka-exhibitions' ),
							value: length,
							options: [
								{ label: __( 'Short', 'kilka-exhibitions' ), value: 'short' },
								{ label: __( 'Normal', 'kilka-exhibitions' ), value: 'normal' },
								{ label: __( 'Long', 'kilka-exhibitions' ), value: 'long' },
								{ label: __( 'Full viewport', 'kilka-exhibitions' ), value: 'viewport' }
							],
							onChange: function ( value ) {
								props.setAttributes( { length: value } );
							}
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( 'span', null, __( 'Pause:', 'kilka-exhibitions' ) + ' ' + length )
				)
			);
		},
		save: function ( props ) {
			var length = preset( props.attributes.length, [ 'short', 'normal', 'long', 'viewport' ], 'normal' );

			return el( 'div', useBlockProps.save( {
				className: 'kilka-exhibition-space kilka-exhibition-pause-space is-length-' + length,
				'aria-hidden': 'true'
			} ) );
		}
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.data,
	window.wp.element,
	window.wp.i18n
);
