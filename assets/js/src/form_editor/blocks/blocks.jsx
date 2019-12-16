import { registerBlockType, setCategories } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import MailPoet from 'mailpoet';
import formatCustomFieldBlockName from './format_custom_field_block_name.jsx';

import * as divider from './divider/divider.jsx';
import * as email from './email/email.jsx';
import * as submit from './submit/submit.jsx';
import * as firstName from './first_name/first_name.jsx';
import * as lastName from './last_name/last_name.jsx';
import * as segmentSelect from './segment_select/segment_select.jsx';
import * as customHtml from './custom_html/custom_html.jsx';

import * as customText from './custom_text/custom_text.jsx';
import * as customTextArea from './custom_textarea/custom_textarea.jsx';

const registerCustomFieldBlock = (customField) => {
  const namesMap = {
    text: {
      name: customText.name,
      settings: customText.getSettings(customField),
    },
    textarea: {
      name: customTextArea.name,
      settings: customTextArea.getSettings(customField),
    },
  };

  if (!namesMap[customField.type]) return;

  registerBlockType(
    formatCustomFieldBlockName(namesMap[customField.type].name, customField),
    namesMap[customField.type].settings
  );
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
