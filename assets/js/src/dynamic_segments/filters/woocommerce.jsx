import MailPoet from 'mailpoet';
import _ from 'underscore';

const actionsField = {
  name: 'action',
  type: 'select',
  values: {
    '': MailPoet.I18n.t('selectActionPlaceholder'),
    purchasedCategory: MailPoet.I18n.t('wooPurchasedCategory'),
    purchasedProduct: MailPoet.I18n.t('wooPurchasedProduct'),
  },
};

const categoriesField = {
  name: 'category_id',
  type: 'selection',
  endpoint: 'product_categories',
  resetSelect2OnUpdate: true,
  placeholder: MailPoet.I18n.t('selectWooPurchasedCategory'),
  forceSelect2: true,
  getLabel: _.property('cat_name'),
  getValue: _.property('term_id'),
};

const productsField = {
  name: 'product_id',
  type: 'selection',
  endpoint: 'products',
  resetSelect2OnUpdate: true,
  placeholder: MailPoet.I18n.t('selectWooPurchasedProduct'),
  forceSelect2: true,
  getLabel: _.property('title'),
  getValue: _.property('ID'),
};

export default (formItems) => {
  const formFields = [actionsField];
  if (formItems.action === 'purchasedCategory') {
    formFields.push(categoriesField);
  }
  if (formItems.action === 'purchasedProduct') {
    formFields.push(productsField);
  }
  return Promise.resolve(formFields);
};
