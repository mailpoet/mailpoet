define(
  [
    'react',
    'react-router',
    'mailpoet',
    'form/form.jsx',
    'form/fields/selection.jsx',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    React,
    Router,
    MailPoet,
    Form,
    Selection,
    Breadcrumb
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
        name: 'segments',
        label: MailPoet.I18n.t('segments'),
        tip: MailPoet.I18n.t('segmentsTip'),
        type: 'selection',
        placeholder: MailPoet.I18n.t('selectSegmentPlaceholder'),
        id: "mailpoet_segments",
        endpoint: "segments",
        multiple: true,
        filter: function(segment) {
          return !!(!segment.deleted_at);
        },
        validation: {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t('noSegmentsSelectedError')
        }
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

    var messages = {
      onUpdate: function() {
        MailPoet.Notice.success(MailPoet.I18n.t('newsletterUpdated'));
      },
      onCreate: function() {
        MailPoet.Notice.success(MailPoet.I18n.t('newsletterAdded'));
      }
    };

    var NewsletterSend = React.createClass({
      mixins: [
        Router.History
      ],
      componentDidMount: function() {
        jQuery('#mailpoet_newsletter').parsley();
      },
      isValid: function() {
        return jQuery('#mailpoet_newsletter').parsley().isValid();
      },
      handleSend: function() {
        if(!this.isValid()) {
          jQuery('#mailpoet_newsletter').parsley().validate();
        } else {
          MailPoet.Ajax.post({
            endpoint: 'sendingQueue',
            action: 'add',
            data: {
              newsletter_id: this.props.params.id,
              segments: jQuery('#mailpoet_segments').val(),
              sender: {
                'name': jQuery('#mailpoet_newsletter [name="sender_name"]').val(),
                'address': jQuery('#mailpoet_newsletter [name="sender_address"]').val()
              },
              reply_to: {
                'name': jQuery('#mailpoet_newsletter [name="reply_to_name"]').val(),
                'address': jQuery('#mailpoet_newsletter [name="reply_to_address"]').val()
              }
            }
          }).done(function(response) {
            if(response.result === true) {
              this.history.pushState(null, '/');
              MailPoet.Notice.success(
                MailPoet.I18n.t('newsletterIsBeingSent')
              );
            } else {
              if(response.errors) {
                MailPoet.Notice.error(response.errors);
              } else {
                MailPoet.Notice.error(
                  MailPoet.I18n.t('newsletterSendingError').replace("%$1s", '?page=mailpoet-settings')
                );
              }
            }
          }.bind(this));
        }
        return false;
      },
      render: function() {
        return (
          <div>
            <h1>{MailPoet.I18n.t('finalNewsletterStep')}</h1>

            <Breadcrumb step="send" />

            <Form
              id="mailpoet_newsletter"
              endpoint="newsletters"
              fields={ fields }
              params={ this.props.params }
              messages={ messages }
              isValid={ this.isValid }
            >
              <p className="submit">
                <input
                  className="button button-primary"
                  type="button"
                  onClick={ this.handleSend }
                  value={MailPoet.I18n.t('send')} />
                &nbsp;
                <input
                  className="button button-secondary"
                  type="submit"
                  value={MailPoet.I18n.t('saveDraftAndClose')} />
                &nbsp;{MailPoet.I18n.t('orSimply')}&nbsp;
                <a
                  href={
                    '?page=mailpoet-newsletter-editor&id='+this.props.params.id
                  }>
                  {MailPoet.I18n.t('goBackToDesign')}
                </a>.
              </p>
            </Form>
          </div>
        );
      }
    });

    return NewsletterSend;
  }
);
