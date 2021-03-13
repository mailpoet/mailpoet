import _ from 'underscore';
import MailPoet from 'mailpoet';

function validate(formItems) {
  if (!formItems.wordpressRole) {
    return false;
  }
  return true;
}

export default (formItems) => Promise.resolve({
  fields: [
    {
      name: 'wordpressRole',
      type: 'select',
      placeholder: MailPoet.I18n.t('selectUserRolePlaceholder'),
      values: window.wordpress_editable_roles_list.reduce((currentValue, accumulator) => (
        _.extend({}, currentValue, { [accumulator.role_id]: accumulator.role_name })
      ), {}),
    },
  ],
  isValid: validate(formItems),
});
