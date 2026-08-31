import { useNavigate, useLocation, useParams } from 'react-router-dom';
import moment from 'moment';
import ReactStringReplace from 'react-string-replace';
import { __, sprintf } from '@wordpress/i18n';
import { Form } from 'form/form.jsx';
import { MailPoet } from 'mailpoet';
import { SubscribersLimitNotice } from 'notices/subscribers-limit-notice';
import { TopBarWithBoundary } from '../common/top-bar/top-bar';
import { BackButton, PageHeader } from '../common/page-header';

interface CustomField {
  id: number;
  name: string;
  type: string;
  params?: {
    values?: Record<string, string>;
  };
}

interface CustomFieldFormField {
  name: string;
  label: string;
  type: string;
  params?: Record<string, unknown>;
  values?: Record<string, string>;
  year_placeholder?: string;
  month_placeholder?: string;
  day_placeholder?: string;
  placeholder?: string;
}

interface Subscriber {
  wp_user_id: number;
  is_woocommerce_user: number;
  subscriptions?: Array<{
    segment_id: number;
    status: string;
    updated_at: string;
  }>;
}

interface Unsubscribe {
  createdAt: {
    date: string;
  };
  source: 'admin' | 'manage' | 'newsletter' | 'mp_api' | string;
  meta?: string;
  newsletterId?: string;
  newsletterSubject?: string;
  reason?: string;
  reasonLabel?: string;
  reasonText?: string;
}

interface FormValues {
  unsubscribes?: Unsubscribe[];
  tracking_consent?: string;
  tracking_consent_updated_at?: string;
  tracking_consent_method?: string;
}

declare global {
  interface Window {
    mailpoet_custom_fields: CustomField[];
    mailpoet_api_version: string;
    mailpoet_timezone_list: string[];
    mailpoet_collect_subscriber_timezones: boolean;
  }
}

interface BaseField {
  name: string;
  label: string;
  type: string;
}

interface TextField extends BaseField {
  type: 'text';
  disabled?: (subscriber: Subscriber) => boolean;
}

interface SelectField extends BaseField {
  type: 'select';
  automationId?: string;
  placeholder?: string;
  tip?: string;
  values: Record<string, string>;
}

interface SelectionField extends BaseField {
  type: 'selection';
  placeholder: string;
  tip: string;
  api_version: string;
  endpoint: string;
  multiple: boolean;
  selected: (subscriber: Subscriber) => number[] | null;
  filter: (segment: unknown) => boolean;
  getLabel: (segment: unknown) => string;
  getCount: (segment: unknown) => number;
  getSearchLabel: (segment: unknown, subscriber: Subscriber) => string;
}

interface TokenField extends BaseField {
  type: 'tokenField';
  placeholder: string;
  suggestedValues: unknown[];
  endpoint: string;
  getName: (tag: unknown) => string;
}

type FormField =
  | TextField
  | SelectField
  | SelectionField
  | TokenField
  | CustomFieldFormField;

const timeZoneField: SelectField = {
  name: 'timezone',
  label: __('Timezone', 'mailpoet'),
  type: 'select',
  automationId: 'subscriber-timezone',
  placeholder: __('Not set', 'mailpoet'),
  values: Object.fromEntries(
    (window.mailpoet_timezone_list || []).map((timezone) => [
      timezone,
      timezone.replaceAll('_', ' '),
    ]),
  ),
};

const fields: FormField[] = [
  {
    name: 'email',
    label: MailPoet.I18n.t('email'),
    type: 'text',
    disabled: function disabled(subscriber: Subscriber) {
      return Boolean(
        Number(subscriber.wp_user_id > 0) ||
          Number(subscriber.is_woocommerce_user) === 1,
      );
    },
  },
  {
    name: 'first_name',
    label: MailPoet.I18n.t('firstname'),
    type: 'text',
    disabled: function disabled(subscriber: Subscriber) {
      return Boolean(
        Number(subscriber.wp_user_id > 0) ||
          Number(subscriber.is_woocommerce_user) === 1,
      );
    },
  },
  {
    name: 'last_name',
    label: MailPoet.I18n.t('lastname'),
    type: 'text',
    disabled: function disabled(subscriber: Subscriber) {
      return Boolean(
        Number(subscriber.wp_user_id > 0) ||
          Number(subscriber.is_woocommerce_user) === 1,
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
    name: 'tracking_consent',
    label: __('Tracking consent', 'mailpoet'),
    type: 'select',
    automationId: 'subscriber-tracking-consent',
    tip: __(
      'Use this only to record consent you received outside MailPoet, for example by email or phone. It does not contact the subscriber.',
      'mailpoet',
    ),
    values: {
      // Keys must match SubscriberEntity::TRACKING_CONSENT_*.
      unknown: __('Not asked', 'mailpoet'),
      granted: __('Granted', 'mailpoet'),
      denied: __('Denied', 'mailpoet'),
    },
  },
  ...(window.mailpoet_collect_subscriber_timezones ? [timeZoneField] : []),
  {
    name: 'segments',
    label: MailPoet.I18n.t('lists'),
    type: 'selection',
    placeholder: MailPoet.I18n.t('selectList'),
    tip: MailPoet.I18n.t('welcomeEmailTip'),
    api_version: window.mailpoet_api_version,
    endpoint: 'segments',
    multiple: true,
    selected: function selected(subscriber: Subscriber) {
      if (Array.isArray(subscriber.subscriptions) === false) {
        return null;
      }

      return subscriber.subscriptions
        .filter((subscription) => subscription.status === 'subscribed')
        .map((subscription) => subscription.segment_id);
    },
    filter: function filter(segment: unknown) {
      return (
        !(segment as { deleted_at?: string })?.deleted_at &&
        (segment as { type?: string })?.type === 'default'
      );
    },
    getLabel: function getLabel(segment: unknown) {
      return (segment as { name?: string })?.name || '';
    },
    getCount: function getCount(segment: unknown) {
      return (segment as { subscribers?: number })?.subscribers || 0;
    },
    getSearchLabel: function getSearchLabel(
      segment: unknown,
      subscriber: Subscriber,
    ) {
      let label = '';

      if (subscriber.subscriptions !== undefined) {
        subscriber.subscriptions.forEach((subscription) => {
          if ((segment as { id?: number })?.id === subscription.segment_id) {
            label = (segment as { name?: string })?.name || '';

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
    getName: function getName(tag: unknown) {
      return Object.prototype.hasOwnProperty.call(tag, 'name')
        ? (tag as { name: string }).name
        : String(tag);
    },
  },
];

const customFields = window.mailpoet_custom_fields || [];
customFields.forEach((customField) => {
  const field: CustomFieldFormField = {
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

function beforeFormContent(subscriber: Subscriber) {
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

// Keys must match SubscriberEntity::TRACKING_CONSENT_*.
const TRACKING_CONSENT_STATE_LABELS: Record<string, string> = {
  unknown: __('Not asked', 'mailpoet'),
  granted: __('Granted', 'mailpoet'),
  denied: __('Denied', 'mailpoet'),
};

// Keys must match SubscriberEntity::TRACKING_CONSENT_METHOD_*.
const TRACKING_CONSENT_METHOD_LABELS: Record<string, string> = {
  footer_link: __('via the email footer link', 'mailpoet'),
  manage_page: __('via the manage-subscription page', 'mailpoet'),
  form: __('via the subscription form', 'mailpoet'),
  admin: __('set by an admin', 'mailpoet'),
  import: __('via CSV import', 'mailpoet'),
  woocommerce_checkout: __('via the WooCommerce checkout', 'mailpoet'),
  registration: __('via WordPress registration', 'mailpoet'),
  comment: __('via a comment', 'mailpoet'),
};

// "State, when, how", labelled. It renders through afterFormContent rather than as
// the field's own `tip` because `fields` is a module-level constant with no access to
// the loaded subscriber, and only afterFormContent is handed getValues(). The label is
// what ties it back to the Tracking consent field further up the form.
//
// The stored wording (tracking_consent_copy) is never shown here: it is evidence, and
// it stays in the database and the export.
function trackingConsentSummary(values: FormValues) {
  const state = values.tracking_consent;
  if (!state) {
    return null;
  }
  const stateLabel = TRACKING_CONSENT_STATE_LABELS[state] || state;
  const updatedAt = values.tracking_consent_updated_at;
  const method = values.tracking_consent_method;
  if (!updatedAt || !method) {
    // Never explicitly set, so there is nothing to date or attribute yet.
    return (
      <p className="description">
        {sprintf(
          /* translators: %s is the consent state, e.g. "Not asked" */
          __('Tracking consent status: %s', 'mailpoet'),
          stateLabel,
        )}
      </p>
    );
  }
  const methodLabel = TRACKING_CONSENT_METHOD_LABELS[method] || method;
  return (
    <p className="description">
      {sprintf(
        /* translators: %1$s is the consent state (e.g. "Denied"), %2$s is a date, %3$s is how it was set (e.g. "via the email footer link") */
        __('Tracking consent status: %1$s, %2$s, %3$s', 'mailpoet'),
        stateLabel,
        MailPoet.Date.format(updatedAt),
        methodLabel,
      )}
    </p>
  );
}

function afterFormContent(values: FormValues) {
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
            .replace('%2$d', unsubscribe.meta || '');
        } else if (unsubscribe.source === 'manage') {
          message = MailPoet.I18n.t('unsubscribedManage').replace('%1$d', date);
        } else if (unsubscribe.source === 'newsletter') {
          message = ReactStringReplace(
            MailPoet.I18n.t('unsubscribedNewsletter').replace('%1$d', date),
            /\[link\]/g,
            (_match, i) => (
              <a
                key={i}
                href={`admin.php?page=mailpoet-newsletter-editor&id=${
                  unsubscribe.newsletterId || ''
                }`}
              >
                {unsubscribe.newsletterSubject || ''}
              </a>
            ),
          );
        } else if (unsubscribe.source === 'mp_api') {
          message = MailPoet.I18n.t('unsubscribedMpApi').replace('%1$d', date);
        } else {
          message = MailPoet.I18n.t('unsubscribedUnknown').replace(
            '%1$d',
            date,
          );
        }
        const reason = unsubscribe.reasonLabel
          ? MailPoet.I18n.t(
              unsubscribe.reasonText
                ? 'unsubscribeReasonWithDetails'
                : 'unsubscribeReason',
            )
              .replace('%1$s', unsubscribe.reasonLabel)
              .replace('%2$s', unsubscribe.reasonText || '')
          : null;
        return (
          <div
            className="description"
            key={`${unsubscribe.source}-${date}-${
              unsubscribe.newsletterId || ''
            }`}
          >
            <p>{message}</p>
            {reason && <p>{reason}</p>}
          </div>
        );
      })}
      {trackingConsentSummary(values)}
      <p className="description">
        <strong>{MailPoet.I18n.t('tip')}</strong>{' '}
        {MailPoet.I18n.t('customFieldsTip')}
      </p>
    </>
  );
}

function SubscriberForm() {
  const location = useLocation();
  const params = useParams();
  const navigate = useNavigate();
  const backUrl = (location.state?.backUrl as string) || '/';
  return (
    <div className="mailpoet-main-container">
      <TopBarWithBoundary hideScreenOptions />

      <PageHeader
        heading={
          params.id
            ? __('Edit subscriber', 'mailpoet')
            : __('Add new subscriber', 'mailpoet')
        }
        headingPrefix={
          <BackButton
            onClick={() => navigate(backUrl)}
            label={MailPoet.I18n.t('backToList')}
          />
        }
      />

      <SubscribersLimitNotice />

      <Form
        automationId="subscriber_edit_form"
        endpoint="subscribers"
        fields={fields}
        params={params}
        messages={messages}
        beforeFormContent={beforeFormContent}
        afterFormContent={afterFormContent}
        onSuccess={() => navigate(backUrl)}
      />
    </div>
  );
}

SubscriberForm.displayName = 'SubscriberForm';

export { SubscriberForm };
