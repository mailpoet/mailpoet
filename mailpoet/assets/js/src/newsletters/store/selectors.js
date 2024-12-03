import { __ } from '@wordpress/i18n';

export const getNewsletterRows = (state) => {
  const data = state.newsletterListing?.data || [];
  const mailpoetTrackingEnabled = state.settings?.mailpoetTrackingEnabled;
  const gutenbergEmailEditorSupported = MailPoet.FeaturesController.isSupported('gutenberg_email_editor');

  const columns = [
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

  return data.map((item) => {
    return columns
      .filter((column) => column.display !== false)
      .map((column) => {
        let value;
        let display;

        switch (column.key) {
          case 'name':
          case 'subject':
            value = item[column.key];
            display = value || '';
            break;
          case 'status':
            value = item.status;
            display = value || '';
            break;
          case 'segments':
            value = item.segments;
            display = value && value.length ? value.map((s) => s.name).join(', ') : '';
            break;
          case 'statistics':
            value = item.statistics;
            display = `Clicked: ${value.clicked}, Opened: ${value.opened}`;
            break;
          case 'sent_at':
            value = item.sent_at;
            display = value ? new Date(value).toLocaleString() : '';
            break;
          default:
            value = item[column.key];
            display = value || '';
        }

        return { display, value };
      });
  });
};