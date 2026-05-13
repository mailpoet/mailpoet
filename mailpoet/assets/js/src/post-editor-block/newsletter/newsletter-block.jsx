import { Icon } from './icon.jsx';
import { Edit } from './edit.jsx';

const wp = window.wp;
const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;

const attributes = {
  newsletterId: {
    type: 'number',
    default: null,
  },
  height: {
    type: 'number',
    default: 800,
  },
  width: {
    type: 'number',
    default: 640,
  },
  showFallbackLink: {
    type: 'boolean',
    default: true,
  },
  fallbackLinkAlignment: {
    type: 'string',
    default: 'center',
  },
  iframeAlignment: {
    type: 'string',
    default: 'center',
  },
  showEmailBackground: {
    type: 'boolean',
    default: true,
  },
  align: {
    type: 'string',
  },
};

registerBlockType('mailpoet/newsletter-render', {
  title: __('MailPoet Newsletter', 'mailpoet'),
  apiVersion: 3,
  attributes,
  supports: {
    inserter: false,
  },
  save() {
    return null;
  },
});

registerBlockType('mailpoet/newsletter', {
  title: __('MailPoet Newsletter', 'mailpoet'),
  apiVersion: 3,
  icon: Icon,
  category: 'widgets',
  example: {},
  attributes,
  supports: {
    align: ['wide', 'full'],
  },
  edit: Edit,
  save() {
    return null;
  },
});
