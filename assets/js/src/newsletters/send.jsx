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

    var settings = window.mailpoet_settings || {};

    var fields = [
      {
        name: 'subject',
        label: 'Subject line',
        tip: "Be creative! It's the first thing your subscribers see."+
             "Tempt them to open your email.",
        type: 'text',
        validation: {
          'data-parsley-required': true
        }
      },
      {
        name: 'segments',
        label: 'Segments',
        tip: "The subscriber segment that will be used for this campaign.",
        type: 'selection',
        placeholder: "Select a segment",
        id: "mailpoet_segments",
        endpoint: "segments",
        multiple: true,
        filter: function(segment) {
          return !!(!segment.deleted_at);
        },
        validation: {
          'data-parsley-required': true
        }
      },
      {
        name: 'sender',
        label: 'Sender',
        tip: "Name & email of yourself or your company.",
        fields: [
          {
            name: 'sender_name',
            type: 'text',
            placeholder: 'John Doe',
            defaultValue: (settings.sender !== undefined) ? settings.sender.name : '',
            validation: {
              'data-parsley-required': true
            }
          },
          {
            name: 'sender_address',
            type: 'text',
            placeholder: 'john.doe@email.com',
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
        label: 'Reply-to',
        tip: 'When the subscribers hit "reply" this is who will receive their '+
             'email.',
        inline: true,
        fields: [
          {
            name: 'reply_to_name',
            type: 'text',
            placeholder: 'John Doe',
            defaultValue: (settings.reply_to !== undefined) ? settings.reply_to.name : '',
          },
          {
            name: 'reply_to_address',
            type: 'text',
            placeholder: 'john.doe@email.com',
            defaultValue: (settings.reply_to !== undefined) ? settings.reply_to.address : ''
          },
        ]
      }
    ];

    var messages = {
      onUpdate: function() {
        MailPoet.Notice.success('Newsletter successfully updated!');
      },
      onCreate: function() {
        MailPoet.Notice.success('Newsletter successfully added!');
      }
    };

    var NewsletterSend = React.createClass({
      mixins: [
        Router.History
      ],
      handleSend: function() {
        if(jQuery('#mailpoet_newsletter').parsley().validate() === true) {
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
                'The newsletter is being sent...'
              );
            } else {
              if(response.errors) {
                MailPoet.Notice.error(
                  response.errors.join("<br />")
                );
              } else {
                MailPoet.Notice.error(
                  'An error occurred while trying to send. '+
                  '<a href="?page=mailpoet-settings">Check your settings.</a>'
                );
              }
            }
          }.bind(this));
        }
      },
      componentDidMount: function() {
        if(this.isMounted()) {
          jQuery('#mailpoet_newsletter').parsley();
        }
      },
      isValid: function() {
        return (jQuery('#mailpoet_newsletter').parsley().validate());
      },
      render: function() {
        return (
          <div>
            <h1>Final step: last details</h1>

            <Breadcrumb step="send" />

            <Form
              id="mailpoet_newsletter"
              endpoint="newsletters"
              fields={ fields }
              params={ this.props.params }
              messages={ messages }
              isValid={ this.isValid }>

              <p className="submit">
                <input
                  className="button button-primary"
                  type="button"
                  onClick={ this.handleSend }
                  value="Send" />
                &nbsp;
                <input
                  className="button button-secondary"
                  type="submit"
                  value="Save as draft and close" />
                &nbsp;or simply&nbsp;
                <a
                  href={
                    '?page=mailpoet-newsletter-editor&id='+this.props.params.id
                  }>
                  go back to design
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