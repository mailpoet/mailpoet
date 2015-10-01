define(
  [
    'react',
    'mailpoet',
    'form/form.jsx',
    'form/fields/selection.jsx',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    React,
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
        name: 'list',
        label: 'Lists',
        tip: "The subscriber list that will be used for this campaign.",
        field: (
          <Selection
            placeholder="Select a list"
            id="mailpoet_segments"
            endpoint="segments" />
        )
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
      updated: function() {
        MailPoet.Notice.success('Newsletter succesfully updated!');
      }
    };

    var NewsletterSend = React.createClass({
      handleSend: function() {
        console.log('send.');
        console.log(jQuery('#mailpoet_newsletter').serializeArray());
        console.log(jQuery('#mailpoet_segments').val());
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