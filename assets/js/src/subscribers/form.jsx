define(
  [
    'react',
    'react-router',
    'mailpoet',
    'form/form.jsx',
    'react-string-replace',
  ],
  (
    React,
    Router,
    MailPoet,
    Form,
    ReactStringReplace
  ) => {
    const fields = [
      {
        name: 'email',
        label: MailPoet.I18n.t('email'),
        type: 'text',
        disabled: function (subscriber) {
          return Number(subscriber.wp_user_id > 0);
        },
      },
      {
        name: 'first_name',
        label: MailPoet.I18n.t('firstname'),
        type: 'text',
        disabled: function (subscriber) {
          return Number(subscriber.wp_user_id > 0);
        },
      },
      {
        name: 'last_name',
        label: MailPoet.I18n.t('lastname'),
        type: 'text',
        disabled: function (subscriber) {
          return Number(subscriber.wp_user_id > 0);
        },
      },
      {
        name: 'status',
        label: MailPoet.I18n.t('status'),
        type: 'select',
        values: {
          subscribed: MailPoet.I18n.t('subscribed'),
          unconfirmed: MailPoet.I18n.t('unconfirmed'),
          unsubscribed: MailPoet.I18n.t('unsubscribed'),
          bounced: MailPoet.I18n.t('bounced'),
        },
        filter: function (subscriber, value) {
          if (Number(subscriber.wp_user_id) > 0 && value === 'unconfirmed') {
            return false;
          }
          return true;
        },
      },
      {
        name: 'segments',
        label: MailPoet.I18n.t('lists'),
        type: 'selection',
        placeholder: MailPoet.I18n.t('selectList'),
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
        multiple: true,
        selected: function (subscriber) {
          if (Array.isArray(subscriber.subscriptions) === false) {
            return null;
          }

          return subscriber.subscriptions
            .filter(subscription => subscription.status === 'subscribed')
            .map(subscription => subscription.segment_id);
        },
        filter: function (segment) {
          return (!segment.deleted_at && segment.type === 'default');
        },
        getLabel: function (segment) {
          return `${segment.name} (${segment.subscribers})`;
        },
        getSearchLabel: function (segment, subscriber) {
          let label = '';

          if (subscriber.subscriptions !== undefined) {
            subscriber.subscriptions.forEach((subscription) => {
              if (segment.id === subscription.segment_id) {
                label = segment.name;

                if (subscription.status === 'unsubscribed') {
                  const unsubscribedAt = MailPoet.Date
                    .format(subscription.updated_at);
                  label += ' (%$1s)'.replace(
                    '%$1s',
                    MailPoet.I18n.t('unsubscribedOn').replace(
                      '%$1s',
                      unsubscribedAt
                    )
                  );
                }
              }
            });
          }
          return label;
        },
      },
    ];

    const customFields = window.mailpoet_custom_fields || [];
    customFields.forEach((customField) => {
      const field = {
        name: `cf_${customField.id}`,
        label: customField.name,
        type: customField.type,
      };
      if (customField.params) {
        field.params = customField.params;
      }

      if (customField.params.values) {
        field.values = customField.params.values;
      }

      // add placeholders for selects (date, select)
      switch (customField.type) {
        case 'date':
          field.year_placeholder = MailPoet.I18n.t('year');
          field.month_placeholder = MailPoet.I18n.t('month');
          field.day_placeholder = MailPoet.I18n.t('day');
          break;

        case 'select':
          field.placeholder = '-';
          break;

        default:
          field.placeholder = '';
          break;
      }

      fields.push(field);
    });

    const messages = {
      onUpdate: function () {
        MailPoet.Notice.success(MailPoet.I18n.t('subscriberUpdated'));
      },
      onCreate: function () {
        MailPoet.Notice.success(MailPoet.I18n.t('subscriberAdded'));
        MailPoet.trackEvent('Subscribers > Add new', {
          'MailPoet Free version': window.mailpoet_version,
        });
      },
    };

    const beforeFormContent = function (subscriber) {
      if (Number(subscriber.wp_user_id) > 0) {
        return (
          <p className="description">
            { ReactStringReplace(
                MailPoet.I18n.t('WPUserEditNotice'),
                /\[link\](.*?)\[\/link\]/g,
                (match, i) => (
                  <a
                    key={i}
                    href={`user-edit.php?user_id=${subscriber.wp_user_id}`}
                  >{ match }</a>
                )
              )
            }
          </p>
        );
      }
      return undefined;
    };

    const afterFormContent = function () {
      return (
        <p className="description">
          <strong>
            { MailPoet.I18n.t('tip') }
          </strong> { MailPoet.I18n.t('customFieldsTip') }
        </p>
      );
    };

    const Link = Router.Link;

    const SubscriberForm = React.createClass({
      render: function () {
        return (
          <div>
            <h1 className="title">
              {MailPoet.I18n.t('subscriber')}
              <Link className="page-title-action" to="/">{MailPoet.I18n.t('backToList')}</Link>
            </h1>

            <Form
              endpoint="subscribers"
              fields={fields}
              params={this.props.params}
              messages={messages}
              beforeFormContent={beforeFormContent}
              afterFormContent={afterFormContent}
            />
          </div>
        );
      },
    });

    return SubscriberForm;
  }
);
