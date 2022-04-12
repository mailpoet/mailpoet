/**
 * External dependencies
 */
/* eslint-disable react/react-in-jsx-scope */
import { Icon, megaphone } from '@wordpress/icons';
import {
  registerBlockType,
  getCategories,
  setCategories,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import { marketingOptinAttributes } from './attributes';
import metadata from './block.json';

const categories = getCategories();
setCategories([...categories, { slug: 'mailpoet', title: 'MailPoet' }]);

registerBlockType(metadata, {
  icon: {
    src: <Icon icon={megaphone} />,
    foreground: '#7f54b3',
  },
  attributes: {
    ...metadata.attributes,
    ...marketingOptinAttributes,
  },
  edit: Edit,
  save: Save,
});
