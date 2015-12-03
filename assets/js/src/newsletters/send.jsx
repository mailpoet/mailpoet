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
        type: 'text'
      },
      {
        name: 'segments',
        label: 'Lists',
        tip: "The subscriber list that will be used for this campaign.",
        type: 'selection',
        placeholder: "Select a list",
        id: "mailpoet_segments",
        endpoint: "segments",
        multiple: true,
        filter: function(segment) {
          return !!(!segment.deleted_at);
        }
      },
      {
        name: 'sender',
        label: 'Sender',
        tip: "Name & email of yourself or your company.",
        fields: [
          {
            name: 'from_name',
            type: 'text',
            placeholder: 'John Doe',
            defaultValue: settings.from_name
          },
          {
            name: 'from_email',
            type: 'text',
            placeholder: 'john.doe@email.com',
            defaultValue: settings.from_address
          },
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
            placeholder: 'John Doe'
          },
          {
            name: 'reply_to_email',
            type: 'text',
            placeholder: 'john.doe@email.com'
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
        MailPoet.Ajax.post({
          endpoint: 'sendingQueue',
          action: 'addQueue',
          data: {
            newsletter_id: this.props.params.id,
            segments: jQuery('#mailpoet_segments').val()
          }
        }).done(function(response) {
          if(response === true) {
            //this.history.pushState(null, '/');

            MailPoet.Notice.success(
              'The newsletter has been sent!'
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
              messages={ messages }>

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