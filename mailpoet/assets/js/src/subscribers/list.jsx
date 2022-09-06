import classnames from 'classnames';
import jQuery from 'jquery';
import { Link, useLocation } from 'react-router-dom';
import PropTypes from 'prop-types';

import { Button, Tags } from 'common';
import { Listing } from 'listing/listing.jsx';
import { MailPoet } from 'mailpoet';
import { Modal } from 'common/modal/modal.tsx';
import { Selection } from 'form/fields/selection.jsx';
import { SubscribersLimitNotice } from 'notices/subscribers_limit_notice.jsx';
import { InvalidMssKeyNotice } from 'notices/invalid_mss_key_notice';
import { EmailVolumeLimitNotice } from 'notices/email_volume_limit_notice';
import { SubscribersCacheMessage } from 'common/subscribers_cache_message';
import { SubscribersInPlan } from 'common/subscribers_in_plan';
import { ListingsEngagementScore } from './listings_engagement_score';
import { SubscribersHeading } from './heading';

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

const columns = [
  {
    name: 'email',
    label: MailPoet.I18n.t('subscriber'),
    sortable: true,
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
    sortable: true,
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('lists'),
  },
  {
    name: 'tags',
    label: MailPoet.I18n.t('tags'),
  },
  {
    name: 'statistics',
    label: MailPoet.I18n.t('statisticsColumn'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'created_at',
    label: MailPoet.I18n.t('subscribedOn'),
    sortable: true,
  },
];

const messages = {
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = MailPoet.I18n.t('oneSubscriberTrashed');
    } else {
      message = MailPoet.I18n.t('multipleSubscribersTrashed').replace(
        '%1$d',
        count.toLocaleString(),
      );
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = MailPoet.I18n.t('oneSubscriberDeleted');
    } else {
      message = MailPoet.I18n.t('multipleSubscribersDeleted').replace(
        '%1$d',
        count.toLocaleString(),
      );
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = MailPoet.I18n.t('oneSubscriberRestored');
    } else {
      message = MailPoet.I18n.t('multipleSubscribersRestored').replace(
        '%1$d',
        count.toLocaleString(),
      );
    }
    MailPoet.Notice.success(message);
  },
  onNoItemsFound: (group) => {
    if (
      group === 'bounced' &&
      !window.mailpoet_premium_active &&
      !window.mailpoet_mss_active
    ) {
      return (
        <div>
          <p>{MailPoet.I18n.t('bouncedSubscribersHelp')}</p>
          <p>
            <a
              href="admin.php?page=mailpoet-upgrade"
              className="button-primary"
            >
              {MailPoet.I18n.t('bouncedSubscribersPremiumButtonText')}
            </a>
          </p>
        </div>
      );
    }
    // use default message
    return false;
  },
};

const createModal = (submitModal, closeModal, field, title) => (
  <Modal title={title} onRequestClose={closeModal} isDismissible>
    <Selection field={field} />
    <span className="mailpoet-gap-half" />
    <Button onClick={submitModal} dimension="small" variant="secondary">
      {MailPoet.I18n.t('apply')}
    </Button>
  </Modal>
);

const bulkActions = [
  {
    name: 'moveToList',
    label: MailPoet.I18n.t('moveToList'),
    onSelect: function onSelect(submitModal, closeModal) {
      const field = {
        id: 'move_to_segment',
        name: 'move_to_segment',
        endpoint: 'segments',
        filter: function filter(segment) {
          return !!(!segment.deleted_at && segment.type === 'default');
        },
      };

      return createModal(
        submitModal,
        closeModal,
        field,
        MailPoet.I18n.t('moveToList'),
      );
    },
    getData: function getData() {
      return {
        segment_id: Number(jQuery('#move_to_segment').val()),
      };
    },
    onSuccess: function onSuccess(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersMovedToList')
          .replace('%1$d', Number(response.meta.count).toLocaleString())
          .replace('%2$s', response.meta.segment),
      );
    },
  },
  {
    name: 'addToList',
    label: MailPoet.I18n.t('addToList'),
    onSelect: function onSelect(submitModal, closeModal) {
      const field = {
        id: 'add_to_segment',
        name: 'add_to_segment',
        endpoint: 'segments',
        filter: function filter(segment) {
          return !!(!segment.deleted_at && segment.type === 'default');
        },
      };

      return createModal(
        submitModal,
        closeModal,
        field,
        MailPoet.I18n.t('addToList'),
      );
    },
    getData: function getData() {
      return {
        segment_id: Number(jQuery('#add_to_segment').val()),
      };
    },
    onSuccess: function onSuccess(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersAddedToList')
          .replace('%1$d', Number(response.meta.count).toLocaleString())
          .replace('%2$s', response.meta.segment),
      );
    },
  },
  {
    name: 'removeFromList',
    label: MailPoet.I18n.t('removeFromList'),
    onSelect: function onSelect(submitModal, closeModal) {
      const field = {
        id: 'remove_from_segment',
        name: 'remove_from_segment',
        endpoint: 'segments',
        filter: function filter(segment) {
          return !!(segment.type === 'default');
        },
      };

      return createModal(
        submitModal,
        closeModal,
        field,
        MailPoet.I18n.t('removeFromList'),
      );
    },
    getData: function getData() {
      return {
        segment_id: Number(jQuery('#remove_from_segment').val()),
      };
    },
    onSuccess: function onSuccess(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersRemovedFromList')
          .replace('%1$d', Number(response.meta.count).toLocaleString())
          .replace('%2$s', response.meta.segment),
      );
    },
  },
  {
    name: 'removeFromAllLists',
    label: MailPoet.I18n.t('removeFromAllLists'),
    onSuccess: function onSuccess(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersRemovedFromAllLists').replace(
          '%1$d',
          Number(response.meta.count).toLocaleString(),
        ),
      );
    },
  },
  {
    name: 'trash',
    label: MailPoet.I18n.t('moveToTrash'),
    onSuccess: messages.onTrash,
  },
  {
    name: 'unsubscribe',
    label: MailPoet.I18n.t('unsubscribe'),
    onSelect: (submitModal, closeModal, bulkActionProps) => {
      const count =
        bulkActionProps.selection !== 'all'
          ? bulkActionProps.selected_ids.length
          : bulkActionProps.count;
      return (
        <Modal
          title={MailPoet.I18n.t('unsubscribe')}
          onRequestClose={closeModal}
          isDismissible
        >
          <p>
            {MailPoet.I18n.t('unsubscribeConfirm').replace(
              '%s',
              count.toLocaleString(),
            )}
          </p>
          <span className="mailpoet-gap-half" />
          <Button
            onClick={submitModal}
            dimension="small"
            variant="secondary"
            automationId="bulk-unsubscribe-confirm"
          >
            {MailPoet.I18n.t('apply')}
          </Button>
        </Modal>
      );
    },
  },
];

const itemActions = [
  {
    name: 'statistics',
    label: MailPoet.I18n.t('statsListingActionTitle'),
    link: function link(subscriber, location) {
      return (
        <Link
          to={{
            pathname: `/stats/${subscriber.id}`,
            state: {
              backUrl: location?.pathname,
            },
          }}
        >
          {MailPoet.I18n.t('statsListingActionTitle')}
        </Link>
      );
    },
  },
  {
    name: 'edit',
    label: MailPoet.I18n.t('edit'),
    link: function link(subscriber, location) {
      return (
        <Link
          to={{
            pathname: `/edit/${subscriber.id}`,
            state: {
              backUrl: location?.pathname,
            },
          }}
        >
          {MailPoet.I18n.t('edit')}
        </Link>
      );
    },
  },
  {
    name: 'sendConfirmationEmail',
    className: 'mailpoet-hide-on-mobile',
    label: MailPoet.I18n.t('resendConfirmationEmail'),
    display: function display(subscriber) {
      return (
        subscriber.status === 'unconfirmed' &&
        subscriber.count_confirmations < window.mailpoet_max_confirmation_emails
      );
    },
    onClick: function onClick(subscriber) {
      return MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'subscribers',
        action: 'sendConfirmationEmail',
        data: {
          id: subscriber.id,
        },
      })
        .done(() =>
          MailPoet.Notice.success(MailPoet.I18n.t('oneConfirmationEmailSent')),
        )
        .fail((response) => MailPoet.Notice.showApiErrorNotice(response));
    },
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
  },
];

const isItemDeletable = (subscriber) => {
  const isDeletable =
    Number(subscriber.wp_user_id) === 0 &&
    Number(subscriber.is_woocommerce_user) === 0;
  return isDeletable;
};

const getSegmentFromId = (segmentId) => {
  let result = false;
  window.mailpoet_segments.forEach((segment) => {
    if (segment.id === segmentId) {
      result = segment;
    }
  });
  return result;
};

function SubscriberList({ match }) {
  const location = useLocation();

  const renderItem = (subscriber, actions) => {
    const rowClasses = classnames(
      'manage-column',
      'column-primary',
      'has-row-actions',
      'column-username',
    );

    let status = '';

    switch (subscriber.status) {
      case 'subscribed':
        status = MailPoet.I18n.t('subscribed');
        break;

      case 'unconfirmed':
        status = MailPoet.I18n.t('unconfirmed');
        break;

      case 'unsubscribed':
        status = MailPoet.I18n.t('unsubscribed');
        break;

      case 'inactive':
        status = MailPoet.I18n.t('inactive');
        break;

      case 'bounced':
        status = MailPoet.I18n.t('bounced');
        break;

      default:
        status = 'Invalid';
        break;
    }

    const subscribedSegments = [];

    // Subscriptions
    if (subscriber.subscriptions.length > 0) {
      subscriber.subscriptions.forEach((subscription) => {
        const segment = getSegmentFromId(subscription.segment_id);
        if (segment === false) return;
        if (subscription.status === 'subscribed') {
          subscribedSegments.push(segment);
        }
      });
    }

    return (
      <div>
        <td className={rowClasses}>
          <Link
            className="mailpoet-listing-title"
            to={{
              pathname: `/edit/${subscriber.id}`,
              state: {
                backUrl: location?.pathname,
              },
            }}
          >
            {subscriber.email}
          </Link>
          <div className="mailpoet-listing-subtitle">
            {subscriber.first_name} {subscriber.last_name}
          </div>
          {actions}
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('status')}>
          {status}
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('lists')}>
          <Tags segments={subscribedSegments} dimension="large" />
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('tags')}>
          <Tags
            subscriberTags={subscriber.tags}
            variant="wordpress"
            isInverted
          />
        </td>
        {mailpoetTrackingEnabled === true ? (
          <td
            className="column mailpoet-listing-stats-column"
            data-colname={MailPoet.I18n.t('statisticsColumn')}
          >
            <div className="mailpoet-listing-stats">
              <a
                key={`stats-link-${subscriber.id}`}
                href={`#/stats/${subscriber.id}`}
              >
                <ListingsEngagementScore
                  id={subscriber.id}
                  engagementScore={subscriber.engagement_score}
                />
              </a>
            </div>
          </td>
        ) : null}
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('subscribedOn')}
        >
          {MailPoet.Date.short(subscriber.created_at)}
          <br />
          {MailPoet.Date.time(subscriber.created_at)}
        </td>
      </div>
    );
  };

  return (
    <div>
      <SubscribersHeading />

      <SubscribersInPlan
        subscribersInPlan={MailPoet.subscribersCount}
        subscribersInPlanLimit={MailPoet.subscribersLimit}
      />

      <SubscribersLimitNotice />
      <EmailVolumeLimitNotice />
      <InvalidMssKeyNotice
        mssKeyInvalid={window.mailpoet_mss_key_invalid}
        subscribersCount={window.mailpoet_subscribers_count}
      />

      <SubscribersCacheMessage
        cacheCalculation={window.mailpoet_subscribers_counts_cache_created_at}
      />

      <Listing
        limit={window.mailpoet_listing_per_page}
        location={location}
        params={match.params}
        endpoint="subscribers"
        onRenderItem={renderItem}
        columns={columns}
        bulk_actions={bulkActions}
        item_actions={itemActions}
        messages={messages}
        sort_by="created_at"
        sort_order="desc"
        isItemDeletable={isItemDeletable}
      />
    </div>
  );
}

SubscriberList.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};

export { SubscriberList };
