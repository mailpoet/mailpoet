define(
  [
    'react',
    'react-router',
    'mailpoet',
    'form/form.jsx'
  ],
  function(
    React,
    Router,
    MailPoet,
    Form
  ) {

    var fields = [
      {
        name: 'email',
        label: 'E-mail',
        type: 'text'
      },
      {
        name: 'first_name',
        label: 'Firstname',
        type: 'text'
      },
      {
        name: 'last_name',
        label: 'Lastname',
        type: 'text'
      },
      {
        name: 'status',
        label: 'Status',
        type: 'select',
        values: {
          'unconfirmed': 'Unconfirmed',
          'subscribed': 'Subscribed',
          'unsubscribed': 'Unsubscribed'
        }
      },
      {
        name: 'segments',
        label: 'Lists',
        type: 'selection',
        placeholder: "Select a list",
        endpoint: "segments",
        multiple: true,
        filter: function(segment) {
          return !!(!segment.deleted_at);
        }
      }
    ];

    var custom_fields = window.mailpoet_custom_fields || [];
    custom_fields.map(custom_field => {
      if(custom_field.type === 'input') {
        custom_field.type = 'text';
      }

      let field = {
        name: 'cf_' + custom_field.id,
        label: custom_field.name,
        type: custom_field.type
      };
      if(custom_field.params) {
        field.params = custom_field.params;
      }

      if(custom_field.params.values) {
        field.values = custom_field.params.values;
      }

      fields.push(field);
    });

    var messages = {
      onUpdate: function() {
        MailPoet.Notice.success('Subscriber successfully updated!');
      },
      onCreate: function() {
        MailPoet.Notice.success('Subscriber successfully added!');
      }
    };

    var Link = Router.Link;

    var SubscriberForm = React.createClass({
      mixins: [
        Router.History
      ],
      render: function() {
        return (
          <div>
            <h2 className="title">
              Subscriber
            </h2>

            <Form
              endpoint="subscribers"
              fields={ fields }
              params={ this.props.params }
              messages={ messages }
            />
          </div>
        );
      }
    });

    return SubscriberForm;
  }
);
