/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';
import { TextControl } from '@wordpress/components';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
import {useBlockProps} from "@wordpress/block-editor";

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType('emuzone-plugin/voting', {
	attributes: {
		voteID: {
			type: 'string',
			source: 'text',
			default: ''
		}
	},
	edit: ( { attributes, setAttributes } ) => {
		const blockProps = useBlockProps();
		const updateFieldValue = ( val ) => {
			setAttributes( { voteID: val } );
		}
		return (
			<p { ...blockProps }>
				<TextControl
					label='Vote ID'
					value={ attributes.voteID }
					onChange={ updateFieldValue }
				/>
			</p>
		);
	},
	save: ( { attributes } ) => {
		const blockProps = useBlockProps.save();

		return <div { ...blockProps }> { attributes.voteID } </div>;
	},
});
