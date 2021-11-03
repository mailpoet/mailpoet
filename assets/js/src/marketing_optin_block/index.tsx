/**
 * External dependencies
 */
/* eslint-disable react/react-in-jsx-scope */
import { Icon, megaphone } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';
import metadata from './block.json';

registerBlockType(metadata, {
  icon: {
    src: <Icon icon={megaphone} />,
    foreground: '#7f54b3',
  },
  attributes: {
    ...metadata.attributes,
    ...attributes,
  },
  edit: Edit,
  save: Save,
});
