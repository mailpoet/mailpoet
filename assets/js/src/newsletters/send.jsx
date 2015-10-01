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
            size: 'auto'
          },
          {
            name: 'from_email',
            type: 'text',
            placeholder: 'john.doe@email.com',
            size: 'auto'
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
            placeholder: 'John Doe',
            size: 'auto'
          },
          {
            name: 'reply_to_email',
            type: 'text',
            placeholder: 'john.doe@email.com',
            size: 'auto'
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
      render: function() {
        return (
          <div>
            <h1>Final step: last details</h1>

            <Breadcrumb step="send" />

            <Form
              endpoint="newsletters"
              fields={ fields }
              params={ this.props.params }
              messages={ messages } />
          </div>
        );
      }
    });

    return NewsletterSend;
  }
);