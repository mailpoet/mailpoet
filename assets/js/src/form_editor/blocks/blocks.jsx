import { registerBlockType, setCategories } from '@wordpress/blocks';

import * as email from './email/email.jsx';
import * as submit from './submit/submit.jsx';

export default () => {
  setCategories([
    { slug: 'obligatory', title: '' }, // Blocks from this category are not in block insert popup
  ]);

  registerBlockType(email.name, email.settings);
  registerBlockType(submit.name, submit.settings);
};
