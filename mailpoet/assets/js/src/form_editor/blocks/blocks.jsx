import { registerBlockType, setCategories } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import MailPoet from 'mailpoet';
import formatCustomFieldBlockName from './format_custom_field_block_name.jsx';
import getCustomFieldBlocksSettings from './custom_fields_blocks.jsx';

import * as divider from './divider/divider';
import * as email from './email/email.jsx';
import * as submit from './submit/submit.jsx';
import * as firstName from './first_name/first_name.jsx';
import * as lastName from './last_name/last_name.jsx';
import * as segmentSelect from './segment_select/segment_select.jsx';
import * as html from './html/html.jsx';
import * as addCustomField from './add_custom_field/add_custom_field.jsx';
import * as columns from './columns/columns.jsx';
import * as column from './columns/column.jsx';
import * as heading from './heading/heading.jsx';
import * as paragraph from './paragraph/paragraph';
import * as image from './image/image';

export const registerCustomFieldBlock = (customField) => {
  const namesMap = getCustomFieldBlocksSettings(customField);

  if (!namesMap[customField.type]) return null;

  const blockName = formatCustomFieldBlockName(
    namesMap[customField.type].name,
    customField,
  );
  registerBlockType(blockName, namesMap[customField.type].settings);
  return blockName;
};

export const initBlocks = () => {
  const customFields = select(
    'mailpoet-form-editor',
  ).getAllAvailableCustomFields();

  // Configure Custom HTML block to be available in inserter only for admins
  html.settings.supports.inserter = select(
    'mailpoet-form-editor',
  ).isUserAdministrator();

  const categories = [
    { slug: 'obligatory', title: '' }, // Blocks from this category are not in block insert popup
  ];

  categories.push({
    slug: 'design',
    title: MailPoet.I18n.t('layoutBlocksCategory'),
  });
  categories.push({
    slug: 'fields',
    title: MailPoet.I18n.t('fieldsBlocksCategory'),
  });
  categories.push({
    slug: 'custom-fields',
    title: MailPoet.I18n.t('customFieldsBlocksCategory'),
  });
  setCategories(categories);

  registerBlockType(divider.name, divider.settings);
  registerBlockType(email.name, email.settings);
  registerBlockType(submit.name, submit.settings);
  registerBlockType(firstName.name, firstName.settings);
  registerBlockType(lastName.name, lastName.settings);
  registerBlockType(segmentSelect.name, segmentSelect.settings);
  registerBlockType(html.name, html.settings);
  registerBlockType(addCustomField.name, addCustomField.settings);
  registerBlockType(columns.name, columns.settings);
  registerBlockType(column.name, column.settings);
  registerBlockType(paragraph.name, paragraph.settings);
  registerBlockType(heading.name, heading.settings);
  registerBlockType(image.name, image.settings);

  if (Array.isArray(customFields)) {
    customFields.forEach(registerCustomFieldBlock);
  }
};
