import slugify from 'slugify';
import { registerBlockType, setCategories } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import MailPoet from 'mailpoet';

import * as divider from './divider/divider.jsx';
import * as email from './email/email.jsx';
import * as submit from './submit/submit.jsx';
import * as firstName from './first_name/first_name.jsx';
import * as lastName from './last_name/last_name.jsx';
import * as segmentSelect from './segment_select/segment_select.jsx';
import * as customHtml from './custom_html/custom_html.jsx';

import * as customText from './custom_text/custom_text.jsx';

export function getCustomFieldName(blockName, customField) {
  const name = slugify(customField.name, { lower: true })
    .replace(/[^a-z0-9]+/g, '')
    .replace(/-$/, '');
  return `${blockName}-${name}`;
}

const registerCustomFieldBlock = (customField) => {
  console.log('custom Field', customField);
  // eslint-disable-next-line default-case
  switch (customField.type) {
    case 'text':
      registerBlockType(
        getCustomFieldName(customText.name, customField),
        customText.getSettings(customField)
      );
      break;
  }
};

export default () => {
  const customFields = select('mailpoet-form-editor').getAllAvailableCustomFields();

  const categories = [
    { slug: 'obligatory', title: '' }, // Blocks from this category are not in block insert popup
  ];
  if (Array.isArray(customFields) && customFields.length) {
    categories.push({ slug: 'custom-fields', title: MailPoet.I18n.t('customFieldsBlocksCategory') });
  }
  categories.push({ slug: 'fields', title: MailPoet.I18n.t('fieldsBlocksCategory') });
  setCategories(categories);

  registerBlockType(divider.name, divider.settings);
  registerBlockType(email.name, email.settings);
  registerBlockType(submit.name, submit.settings);
  registerBlockType(firstName.name, firstName.settings);
  registerBlockType(lastName.name, lastName.settings);
  registerBlockType(segmentSelect.name, segmentSelect.settings);
  registerBlockType(customHtml.name, customHtml.settings);

  if (Array.isArray(customFields)) {
    customFields.forEach(registerCustomFieldBlock);
  }
};
