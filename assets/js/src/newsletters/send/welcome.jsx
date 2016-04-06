define(
  [
    'mailpoet',
    'newsletters/types/welcome/scheduling.jsx'
  ],
  function(
    MailPoet,
    Scheduling
  ) {

    var settings = window.mailpoet_settings || {};

    var fields = [
      {
        name: 'subject',
        label: MailPoet.I18n.t('subjectLine'),
        tip: MailPoet.I18n.t('subjectLineTip'),
        type: 'text',
        validation: {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t('emptySubjectLineError')
        }
      },
      {
        name: 'options',
        label: MailPoet.I18n.t('sendWelcomeEmailWhen'),
        type: 'reactComponent',
        component: Scheduling,
      },
      {
        name: 'sender',
        label: MailPoet.I18n.t('sender'),
        tip: MailPoet.I18n.t('senderTip'),
        fields: [
          {
            name: 'sender_name',
            type: 'text',
            placeholder: MailPoet.I18n.t('senderNamePlaceholder'),
            defaultValue: (settings.sender !== undefined) ? settings.sender.name : '',
            validation: {
              'data-parsley-required': true
            }
          },
          {
            name: 'sender_address',
            type: 'text',
            placeholder: MailPoet.I18n.t('senderAddressPlaceholder'),
            defaultValue: (settings.sender !== undefined) ? settings.sender.address : '',
            validation: {
              'data-parsley-required': true,
              'data-parsley-type': 'email'
            }
          }
        ]
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
            defaultValue: (settings.reply_to !== undefined) ? settings.reply_to.name : '',
          },
          {
            name: 'reply_to_address',
            type: 'text',
            placeholder: MailPoet.I18n.t('replyToAddressPlaceholder'),
            defaultValue: (settings.reply_to !== undefined) ? settings.reply_to.address : ''
          },
        ]
      }
    ];

    return fields;
  }
);

