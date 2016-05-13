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
        label: MailPoet.I18n.t('email'),
        type: 'text',
        disabled: function(subscriber) {
          if (subscriber.wp_user_id !== null) return 'disabled';
        }
      },
      {
        name: 'first_name',
        label: MailPoet.I18n.t('firstname'),
        type: 'text',
        disabled: function(subscriber) {
          if (subscriber.wp_user_id !== null) return 'disabled';
        }
      },
      {
        name: 'last_name',
        label: MailPoet.I18n.t('lastname'),
        type: 'text',
        disabled: function(subscriber) {
          if (subscriber.wp_user_id !== null) return 'disabled';
        }
      },
      {
        name: 'status',
        label: MailPoet.I18n.t('status'),
        type: 'select',
        values: {
          'unconfirmed': MailPoet.I18n.t('unconfirmed'),
          'subscribed': MailPoet.I18n.t('subscribed'),
          'unsubscribed': MailPoet.I18n.t('unsubscribed')
        },
        filterValues: function(subscriber) {
          if (subscriber.wp_user_id !== null) {
            delete this.values.unconfirmed;
          }
          return this.values;
        }
      },
      {
        name: 'segments',
        label: MailPoet.I18n.t('lists'),
        type: 'selection',
        placeholder: MailPoet.I18n.t('selectList'),
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
                  label += ' (%$1s)'.replace(
                    '%$1s',
                    MailPoet.I18n.t('unsubscribedOn').replace(
                      '%$1s',
                      unsubscribed_at
                    )
                  );
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
        MailPoet.Notice.success(MailPoet.I18n.t('subscriberUpdated'));
      },
      onCreate: function() {
        MailPoet.Notice.success(MailPoet.I18n.t('subscriberAdded'));
      }
    };

    var beforeFormContent = function(subscriber) {
      if (subscriber.wp_user_id !== null) {
        var content =
          '<p>' +
            MailPoet.I18n.t('wordPressUserNotice')
              .replace('[link]', '<a href="user-edit.php?user_id=' + subscriber.wp_user_id + '">')
              .replace('[/link]', '</a>') +
          '</p>';
        return <div dangerouslySetInnerHTML={ {__html: content} } />
      }
    };

    var Link = Router.Link;

    var SubscriberForm = React.createClass({
      render: function() {
        return (
          <div>
            <h1 className="title">
              {MailPoet.I18n.t('subscriber')}
              <Link className="page-title-action" to="/">{MailPoet.I18n.t('backToList')}</Link>
            </h1>

            <Form
              endpoint="subscribers"
              fields={ fields }
              params={ this.props.params }
              messages={ messages }
              beforeFormContent={ beforeFormContent }
            />
          </div>
        );
      }
    });

    return SubscriberForm;
  }
);
