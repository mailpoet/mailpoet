import _ from 'underscore';
import Hooks from 'wp-js-hooks';
import MailPoet from 'mailpoet';
import SendEventConditions from 'newsletters/automatic_emails/send_event_conditions.jsx';
import GATrackingField from 'newsletters/send/ga_tracking.jsx';

const emails = window.mailpoet_woocommerce_automatic_emails || [];

const extendNewsletterEditorConfig = (defaultConfig, newsletter) => {
  if (newsletter.type !== 'automatic') return defaultConfig;

  const config = defaultConfig;

  return MailPoet.Ajax
    .post({
      api_version: window.mailpoet_api_version,
      endpoint: 'automatic_emails',
      action: 'get_event_shortcodes',
      data: {
        email_slug: newsletter.options.group,
        event_slug: newsletter.options.event,
      },
    })
    .then((response) => {
      if (!_.isObject(response) || !response.data) return config;
      config.shortcodes = { ...config.shortcodes, ...response.data };
      return config;
    })
    .fail((pauseFailResponse) => {
      if (pauseFailResponse.errors.length > 0) {
        MailPoet.Notice.error(
          pauseFailResponse.errors.map((error) => error.message),
          { scroll: true, static: true }
        );
      }
    });
};

Hooks.addFilter('mailpoet_newsletters_editor_extend_config', 'mailpoet', extendNewsletterEditorConfig);

const configureSendPageOptions = (defaultFields, newsletter) => {
  if (newsletter.type !== 'automatic') return defaultFields;

  const email = emails[newsletter.options.group];

  if (!email) return defaultFields;

  const emailOptions = newsletter.options;
  const fields = [
    {
      name: 'email-header',
      label: null,
      tip: null,
      fields: [
        {
          name: 'subject',
          customLabel: MailPoet.I18n.t('subjectLabel'),
          className: 'mailpoet-form-field-subject',
          placeholder: MailPoet.I18n.t('subjectLine'),
          tooltip: MailPoet.I18n.t('subjectLineTip'),
          type: 'text',
          validation: {
            'data-parsley-required': true,
            'data-parsley-required-message': MailPoet.I18n.t('emptySubjectLineError'),
          },
        },
        {
          name: 'preheader',
          customLabel: MailPoet.I18n.t('preheaderLabel'),
          className: 'mailpoet-form-field-preheader',
          placeholder: MailPoet.I18n.t('preheaderLine'),
          tooltip: `${MailPoet.I18n.t('preheaderLineTip1')} ${MailPoet.I18n.t('preheaderLineTip2')}`,
          type: 'textarea',
          validation: {
            'data-parsley-maxlength': 250,
          },
        },
      ],
    },
    {
      name: 'options',
      label: MailPoet.I18n.t('sendAutomaticEmailWhenHeading').replace('%1s', email.title),
      type: 'reactComponent',
      component: SendEventConditions,
      email,
      emailOptions,
    },
    GATrackingField,
    {
      name: 'sender',
      label: MailPoet.I18n.t('sender'),
      tip: MailPoet.I18n.t('senderTip'),
      fields: [
        {
          name: 'sender_name',
          type: 'text',
          placeholder: MailPoet.I18n.t('senderNamePlaceholder'),
          validation: {
            'data-parsley-required': true,
          },
        },
        {
          name: 'sender_address',
          type: 'text',
          placeholder: MailPoet.I18n.t('senderAddressPlaceholder'),
          validation: {
            'data-parsley-required': true,
            'data-parsley-type': 'email',
          },
        },
      ],
    },
    {
      name: 'empty',
      type: 'empty',
    },
    {
      name: 'reply-to',
      label: MailPoet.I18n.t('replyTo'),
      tip: MailPoet.I18n.t('replyToTip'),
      inline: true,
      fields: [
        {
          name: 'reply_to_name',
          type: 'text',
          placeholder: MailPoet.I18n.t('replyToNamePlaceholder'),
        },
        {
          name: 'reply_to_address',
          type: 'text',
          placeholder: MailPoet.I18n.t('replyToAddressPlaceholder'),
          validation: {
            'data-parsley-type': 'email',
          },
        },
      ],
    },
  ];

  return {
    getFields: function getFields() {
      return fields;
    },
    getSendButtonOptions: function getSendButtonOptions() {
      return {
        value: MailPoet.I18n.t('activate'),
      };
    },
  };
};

Hooks.addFilter('mailpoet_newsletters_send_newsletter_fields', 'mailpoet', configureSendPageOptions);

const configureSendPageServerRequest = (defaultParameters, newsletter) => (
  (newsletter.type === 'automatic') ? {
    api_version: window.mailpoet_api_version,
    endpoint: 'newsletters',
    action: 'setStatus',
    data: {
      id: newsletter.id,
      status: 'active',
    },
  } : defaultParameters
);

Hooks.addFilter('mailpoet_newsletters_send_server_request_parameters', 'mailpoet', configureSendPageServerRequest);

const redirectAfterSendPageServerRequest = (defaultRedirect, newsletter) => (
  (newsletter.type === 'automatic') ? `/${newsletter.options.group}` : defaultRedirect
);

Hooks.addFilter('mailpoet_newsletters_send_server_request_response_redirect', 'mailpoet', redirectAfterSendPageServerRequest);

const configureSendPageServerResponse = (newsletter) => {
  if (newsletter.type !== 'automatic') return null;

  const email = emails[newsletter.options.group];

  if (!email) return null;

  return () => {
    MailPoet.Notice.success(MailPoet.I18n.t('automaticEmailActivated').replace('%1s', email.title));
  };
};

Hooks.addFilter('mailpoet_newsletters_send_server_request_response', 'mailpoet', configureSendPageServerResponse);
