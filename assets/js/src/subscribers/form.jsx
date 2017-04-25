define(
  [
    'react',
    'react-router',
    'mailpoet',
    'form/form.jsx',
    'react-string-replace'
  ],
  function(
    React,
    Router,
    MailPoet,
    Form,
    ReactStringReplace
  ) {
    var fields = [
      {
        name: 'email',
        label: MailPoet.I18n.t('email'),
        type: 'text',
        disabled: function(subscriber) {
          return ~~(subscriber.wp_user_id > 0);
        }
      },
      {
        name: 'first_name',
        label: MailPoet.I18n.t('firstname'),
        type: 'text',
        disabled: function(subscriber) {
          return ~~(subscriber.wp_user_id > 0);
        }
      },
      {
        name: 'last_name',
        label: MailPoet.I18n.t('lastname'),
        type: 'text',
        disabled: function(subscriber) {
          return ~~(subscriber.wp_user_id > 0);
        }
      },
      {
        name: 'status',
        label: MailPoet.I18n.t('status'),
        type: 'select',
        values: {
          'subscribed': MailPoet.I18n.t('subscribed'),
          'unconfirmed': MailPoet.I18n.t('unconfirmed'),
          'unsubscribed': MailPoet.I18n.t('unsubscribed'),
          'bounced': MailPoet.I18n.t('bounced')
        },
        filter: function(subscriber, value) {
          if (~~(subscriber.wp_user_id) > 0 && value === 'unconfirmed') {
            return false;
          }
          return true;
        }
      },
      {
        name: 'segments',
        label: MailPoet.I18n.t('lists'),
        type: 'selection',
        placeholder: MailPoet.I18n.t('selectList'),
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
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
          return !!(!segment.deleted_at && segment.type === 'default');
        },
        getLabel: function(segment) {
          return segment.name + ' ('+ segment.subscribers +')';
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

      // add placeholders for selects (date, select)
      switch(custom_field.type) {
        case 'date':
          field.year_placeholder = MailPoet.I18n.t('year');
          field.month_placeholder = MailPoet.I18n.t('month');
          field.day_placeholder = MailPoet.I18n.t('day');
        break;

        case 'select':
          field.placeholder = '-';
        break;
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
      if (~~(subscriber.wp_user_id) > 0) {
        return (
          <p className="description">
            { ReactStringReplace(
                MailPoet.I18n.t('WPUserEditNotice'),
                /\[link\](.*?)\[\/link\]/g,
                (match, i) => (
                  <a
                    key={ i }
                    href={`user-edit.php?user_id=${ subscriber.wp_user_id }`}
                  >{ match }</a>
                )
              )
            }
          </p>
        );
      }
    };

    var afterFormContent = function(subscriber) {
      return (
        <p className="description">
          <strong>
            { MailPoet.I18n.t('tip') }
          </strong> { MailPoet.I18n.t('customFieldsTip') }
        </p>
      );
    }

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
              afterFormContent={ afterFormContent }
            />
          </div>
        );
      }
    });

    return SubscriberForm;
  }
);
