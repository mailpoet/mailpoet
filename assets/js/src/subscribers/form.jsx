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
        selected: function(subscriber) {
          if (Array.isArray(subscriber.subscriptions) === false) {
            return null;
          }

          return subscriber.subscriptions.map(function(subscription) {
            if (subscription.status === 'subscribed') {
              return subscription.segment_id;
            }
          });
        },
        filter: function(segment) {
          return !!(!segment.deleted_at);
        },
        getSearchLabel: function(segment, subscriber) {
          let label = '';

          if (subscriber.subscriptions !== undefined) {
            subscriber.subscriptions.map(function(subscription) {
              if (segment.id === subscription.segment_id) {
                label = segment.name;

                if (subscription.status === 'unsubscribed') {
                  const unsubscribed_at = MailPoet.Date
                    .format(subscription.updated_at);
                  label += ' (Unsubscribed on '+unsubscribed_at+')';
                }
              }
            });
          }
          return label;
        }
      }
    ];

    var custom_fields = window.mailpoet_custom_fields || [];
    custom_fields.map(custom_field => {
      let field = {
        name: 'cf_' + custom_field.id,
        label: custom_field.name,
        type: custom_field.type
      };
      if (custom_field.params) {
        field.params = custom_field.params;
      }

      if (custom_field.params.values) {
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
