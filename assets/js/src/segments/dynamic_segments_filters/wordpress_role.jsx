import _ from 'underscore';
import MailPoet from 'mailpoet';

export default () => Promise.resolve([
  {
    name: 'wordpressRole',
    type: 'select',
    placeholder: MailPoet.I18n.t('selectUserRolePlaceholder'),
    values: window.wordpress_editable_roles_list.reduce((currentValue, accumulator) => (
      _.extend({}, currentValue, { [accumulator.role_id]: accumulator.role_name })
    ), {}),
  },
]);
