/**
 * External dependencies
 */
/* eslint-disable react/react-in-jsx-scope */
import { Icon, button } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';

registerBlockType(metadata, {
  icon: {
    src: <Icon icon={button} />,
    foreground: '#7f54b3',
  },
  edit: Edit,
  save: Save,
});
