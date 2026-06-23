import { Edit } from './edit.jsx';
import { Icon } from '../subscription-form/icon.jsx';

const wp = window.wp;
const { registerBlockType } = wp.blocks;

const locale = window.mailpoetManageSubscriptionBlock || {};
const title = locale.title || 'MailPoet Manage Subscription';

// Inserter-hidden block that is server-rendered for the editor preview.
registerBlockType('mailpoet/manage-subscription-block-render', {
  title,
  apiVersion: 3,
  supports: {
    inserter: false,
  },
});

registerBlockType('mailpoet/manage-subscription-block', {
  title,
  description: locale.description || '',
  apiVersion: 3,
  icon: Icon,
  category: 'widgets',
  example: {},
  edit: Edit,
  save() {
    return null;
  },
});
