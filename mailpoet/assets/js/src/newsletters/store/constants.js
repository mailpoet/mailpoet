import { __ } from '@wordpress/i18n';


export const storeName = 'mailpoet/store/newsletters';

export const EMPTY_NEWSLETTERS = [];
export const EMPTY_NEWSLETTER_ERRORS = [];

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;
const gutenbergEmailEditorSupported = MailPoet.FeaturesController.isSupported('gutenberg_email_editor');

export const NEWSLETTER_STANDARD_HEADERS = [
        {
          key: gutenbergEmailEditorSupported ? 'name' : 'subject',
          label: gutenbergEmailEditorSupported ? __('Name', 'mailpoet') : __('Subject', 'mailpoet'),
          isSortable: true,
        },
        {
          key: 'status',
          label: __('Status', 'mailpoet'),
          isSortable: false,
        },
        {
          key: 'segments',
          label: __('Lists', 'mailpoet'),
          isSortable: false,
        },
        {
          key: 'statistics',
          label: __('Clicked, Opened', 'mailpoet'),
          display: mailpoetTrackingEnabled,
          isSortable: false,
        },
        {
          key: 'sent_at',
          label: __('Sent on', 'mailpoet'),
          isSortable: true,
        },
      ];