import { Link, useHistory, useLocation } from 'react-router-dom';
import moment from 'moment';
import PropTypes from 'prop-types';
import ReactStringReplace from 'react-string-replace';

import { Background } from 'common/background/background';
import { Form } from 'form/form.jsx';
import { Heading } from 'common/typography/heading/heading';
import { HideScreenOptions } from 'common/hide_screen_options/hide_screen_options';
import { MailPoet } from 'mailpoet';
import { SubscribersLimitNotice } from 'notices/subscribers_limit_notice.jsx';

const fields = [
  {
    name: 'email',
    label: MailPoet.I18n.t('email'),
    type: 'text',
    disabled: function disabled(subscriber) {
      return (
        Number(subscriber.wp_user_id > 0) ||
        Number(subscriber.is_woocommerce_user) === 1
      );
    },
  },
  {
    name: 'first_name',
    label: MailPoet.I18n.t('firstname'),
    type: 'text',
    disabled: function disabled(subscriber) {
      return (
        Number(subscriber.wp_user_id > 0) ||
        Number(subscriber.is_woocommerce_user) === 1
      );
    },
  },
  {
    name: 'last_name',
    label: MailPoet.I18n.t('lastname'),
    type: 'text',
    disabled: function disabled(subscriber) {
      return (
        Number(subscriber.wp_user_id > 0) ||
        Number(subscriber.is_woocommerce_user) === 1
      );
    },
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
    type: 'select',
    automationId: 'subscriber-status',
    values: {
      subscribed: MailPoet.I18n.t('subscribed'),
      unconfirmed: MailPoet.I18n.t('unconfirmed'),
      unsubscribed: MailPoet.I18n.t('unsubscribed'),
      inactive: MailPoet.I18n.t('inactive'),
      bounced: MailPoet.I18n.t('bounced'),
    },
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('lists'),
    type: 'selection',
    placeholder: MailPoet.I18n.t('selectList'),
    tip: MailPoet.I18n.t('welcomeEmailTip'),
    api_version: window.mailpoet_api_version,
    endpoint: 'segments',
    multiple: true,
    selected: function selected(subscriber) {
      if (Array.isArray(subscriber.subscriptions) === false) {
        return null;
      }

      return subscriber.subscriptions
        .filter((subscription) => subscription.status === 'subscribed')
        .map((subscription) => subscription.segment_id);
    },
    filter: function filter(segment) {
      return !segment.deleted_at && segment.type === 'default';
    },
    getLabel: function getLabel(segment) {
      return segment.name;
    },
    getCount: function getCount(segment) {
      return segment.subscribers;
    },
    getSearchLabel: function getSearchLabel(segment, subscriber) {
      let label = '';

      if (subscriber.subscriptions !== undefined) {
        subscriber.subscriptions.forEach((subscription) => {
          if (segment.id === subscription.segment_id) {
            label = segment.name;

            if (subscription.status === 'unsubscribed') {
              const unsubscribedAt = MailPoet.Date.format(
                subscription.updated_at,
              );
              label += ' (%1$s)'.replace(
                '%1$s',
                MailPoet.I18n.t('unsubscribedOn').replace(
                  '%1$s',
                  unsubscribedAt,
                ),
              );
            }
          }
        });
      }
      return label;
    },
  },
  {
    name: 'tags',
    label: MailPoet.I18n.t('tags'),
    type: 'tokenField',
    placeholder: MailPoet.I18n.t('addNewTag'),
    suggestedValues: [],
    endpoint: 'tags',
    getName: function getName(tag) {
      return Object.prototype.hasOwnProperty.call(tag, 'name') ? tag.name : tag;
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
    if (customField.params.values) {
      field.values = customField.params.values;
    }
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
  onUpdate: function onUpdate() {
    MailPoet.Notice.success(MailPoet.I18n.t('subscriberUpdated'));
  },
  onCreate: function onCreate() {
    MailPoet.Notice.success(MailPoet.I18n.t('subscriberAdded'));
    MailPoet.trackEvent('Subscribers > Add new');
  },
};

function beforeFormContent(subscriber) {
  if (Number(subscriber.wp_user_id) > 0) {
    return (
      <p className="description">
        {ReactStringReplace(
          MailPoet.I18n.t('WPUserEditNotice'),
          /\[link\](.*?)\[\/link\]/g,
          (match, i) => (
            <a key={i} href={`user-edit.php?user_id=${subscriber.wp_user_id}`}>
              {match}
            </a>
          ),
        )}
      </p>
    );
  }
  return undefined;
}

function afterFormContent(values) {
  return (
    <>
      {values?.unsubscribes?.map((unsubscribe) => {
        const date = moment(unsubscribe.createdAt.date).format(
          'dddd MMMM Do YYYY [at] h:mm:ss a',
        );
        let message;
        if (unsubscribe.source === 'admin') {
          message = MailPoet.I18n.t('unsubscribedAdmin')
            .replace('%1$d', date)
            .replace('%2$d', unsubscribe.meta);
        } else if (unsubscribe.source === 'manage') {
          message = MailPoet.I18n.t('unsubscribedManage').replace('%1$d', date);
        } else if (unsubscribe.source === 'newsletter') {
          message = ReactStringReplace(
            MailPoet.I18n.t('unsubscribedNewsletter').replace('%1$d', date),
            /\[link\]/g,
            (match, i) => (
              <a
                key={i}
                href={`admin.php?page=mailpoet-newsletter-editor&id=${unsubscribe.newsletterId}`}
              >
                {unsubscribe.newsletterSubject}
              </a>
            ),
          );
        } else {
          message = MailPoet.I18n.t('unsubscribedUnknown').replace(
            '%1$d',
            date,
          );
        }
        return (
          <p className="description" key={message}>
            {message}
          </p>
        );
      })}
      <p className="description">
        <strong>{MailPoet.I18n.t('tip')}</strong>{' '}
        {MailPoet.I18n.t('customFieldsTip')}
      </p>
    </>
  );
}

function SubscriberForm({ match }) {
  const location = useLocation();
  const history = useHistory();
  const backUrl = location.state?.backUrl || '/';
  return (
    <div>
      <Background color="#fff" />
      <HideScreenOptions />

      <Heading level={1} className="mailpoet-title">
        <span>{MailPoet.I18n.t('subscriber')}</span>
        <Link
          className="mailpoet-button button button-secondary button-small"
          to={backUrl}
        >
          {MailPoet.I18n.t('backToList')}
        </Link>
      </Heading>

      <SubscribersLimitNotice />

      <Form
        automationId="subscriber_edit_form"
        endpoint="subscribers"
        fields={fields}
        params={match.params}
        messages={messages}
        beforeFormContent={beforeFormContent}
        afterFormContent={afterFormContent}
        onSuccess={() => history.push(backUrl)}
      />
    </div>
  );
}

SubscriberForm.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string,
    }).isRequired,
  }).isRequired,
};

SubscriberForm.displayName = 'SubscriberForm';

export { SubscriberForm };
