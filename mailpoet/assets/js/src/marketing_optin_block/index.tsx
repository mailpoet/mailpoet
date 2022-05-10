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

// Attributes are in metadata vs. settings are used strangely here.
// Needs more investigation to enable correct types.
/* eslint-disable @typescript-eslint/no-explicit-any,@typescript-eslint/no-unsafe-argument */
registerBlockType(
  metadata as any,
  {
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
  } as any,
);
