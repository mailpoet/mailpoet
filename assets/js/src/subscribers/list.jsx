import React from 'react';
import { Link, useLocation } from 'react-router-dom';

import jQuery from 'jquery';
import MailPoet from 'mailpoet';
import classNames from 'classnames';
import PropTypes from 'prop-types';

import Button from 'common/button/button.tsx';
import Listing from 'listing/listing.jsx';
import Modal from 'common/modal/modal.tsx';
import Selection from 'form/fields/selection.jsx';
import SubscribersLimitNotice from 'notices/subscribers_limit_notice.jsx';
import InvalidMssKeyNotice from 'notices/invalid_mss_key_notice';
import SubscribersInPlan from '../common/subscribers_in_plan';

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
      message = (
        MailPoet.I18n.t('oneSubscriberTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleSubscribersTrashed')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneSubscriberDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleSubscribersDeleted')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneSubscriberRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleSubscribersRestored')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onNoItemsFound: (group) => {
    if (group === 'bounced' && !window.mailpoet_premium_active && !window.mailpoet_mss_active) {
      return (
        <div>
          <p>{MailPoet.I18n.t('bouncedSubscribersHelp')}</p>
          <p>
            <a href="admin.php?page=mailpoet-premium" className="button-primary">
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
  <Modal
    title={title}
    onRequestClose={closeModal}
    isDismissible
  >
    <Selection field={field} />
    <span className="mailpoet-gap-half" />
    <Button
      onClick={submitModal}
      dimension="small"
      variant="light"
    >
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
          return !!(
            !segment.deleted_at && segment.type === 'default'
          );
        },
      };

      return createModal(submitModal, closeModal, field, MailPoet.I18n.t('moveToList'));
    },
    getData: function getData() {
      return {
        segment_id: Number(jQuery('#move_to_segment').val()),
      };
    },
    onSuccess: function onSuccess(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersMovedToList')
          .replace('%$1d', (Number(response.meta.count)).toLocaleString())
          .replace('%$2s', response.meta.segment)
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
          return !!(
            !segment.deleted_at && segment.type === 'default'
          );
        },
      };

      return createModal(submitModal, closeModal, field, MailPoet.I18n.t('addToList'));
    },
    getData: function getData() {
      return {
        segment_id: Number(jQuery('#add_to_segment').val()),
      };
    },
    onSuccess: function onSuccess(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersAddedToList')
          .replace('%$1d', (Number(response.meta.count)).toLocaleString())
          .replace('%$2s', response.meta.segment)
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
          return !!(
            segment.type === 'default'
          );
        },
      };

      return createModal(submitModal, closeModal, field, MailPoet.I18n.t('removeFromList'));
    },
    getData: function getData() {
      return {
        segment_id: Number(jQuery('#remove_from_segment').val()),
      };
    },
    onSuccess: function onSuccess(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersRemovedFromList')
          .replace('%$1d', (Number(response.meta.count)).toLocaleString())
          .replace('%$2s', response.meta.segment)
      );
    },
  },
  {
    name: 'removeFromAllLists',
    label: MailPoet.I18n.t('removeFromAllLists'),
    onSuccess: function onSuccess(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersRemovedFromAllLists')
          .replace('%$1d', (Number(response.meta.count)).toLocaleString())
      );
    },
  },
  {
    name: 'trash',
    label: MailPoet.I18n.t('moveToTrash'),
    onSuccess: messages.onTrash,
  },
];

const itemActions = [
  {
    name: 'edit',
    label: MailPoet.I18n.t('edit'),
    link: function link(subscriber) {
      return (
        <Link to={`/edit/${subscriber.id}`}>{MailPoet.I18n.t('edit')}</Link>
      );
    },
  },
  {
    name: 'sendConfirmationEmail',
    label: MailPoet.I18n.t('resendConfirmationEmail'),
    display: function display(subscriber) {
      return subscriber.status === 'unconfirmed' && subscriber.count_confirmations < window.mailpoet_max_confirmation_emails;
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
        .done(() => MailPoet.Notice.success(MailPoet.I18n.t('oneConfirmationEmailSent')))
        .fail((response) => MailPoet.Notice.showApiErrorNotice(response));
    },
  },
  {
    name: 'trash',
    display: function display(subscriber) {
      return Number(subscriber.wp_user_id) === 0 && Number(subscriber.is_woocommerce_user) === 0;
    },
  },
];

const getSegmentFromId = (segmentId) => {
  let result = false;
  window.mailpoet_segments.forEach((segment) => {
    if (segment.id === segmentId) {
      result = segment;
    }
  });
  return result;
};

const SubscriberList = ({ match }) => {
  const location = useLocation();

  const renderItem = (subscriber, actions) => {
    const rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions',
      'column-username'
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

    let segments = false;

    // Subscriptions
    if (subscriber.subscriptions.length > 0) {
      const subscribedSegments = [];

      subscriber.subscriptions.forEach((subscription) => {
        const segment = getSegmentFromId(subscription.segment_id);
        if (segment === false) return;
        if (subscription.status === 'subscribed') {
          subscribedSegments.push(segment.name);
        }
      });

      segments = (
        <span>
          { subscribedSegments.join(', ') }
        </span>
      );
    }

    return (
      <div>
        <td className={rowClasses}>
          <strong>
            <Link
              className="row-title"
              to={{
                pathname: `/edit/${subscriber.id}`,
                state: {
                  backUrl: location?.pathname,
                },
              }}
            >
              { subscriber.email }
            </Link>
          </strong>
          <p style={{ margin: 0 }}>
            { subscriber.first_name }
            {' '}
            { subscriber.last_name }
          </p>
          { actions }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('status')}>
          { status }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('lists')}>
          { segments }
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('subscribedOn')}>
          <abbr>{ MailPoet.Date.format(subscriber.created_at) }</abbr>
        </td>
      </div>
    );
  };

  return (
    <div>
      <h1 className="title">
        {MailPoet.I18n.t('pageTitle')}
        {' '}
        <Link
          className="page-title-action"
          to="/new"
        >
          {MailPoet.I18n.t('new')}
        </Link>
        <a
          className="page-title-action"
          href="?page=mailpoet-import"
          data-automation-id="import-subscribers-button"
        >
          {MailPoet.I18n.t('import')}
        </a>
        <a
          id="mailpoet_export_button"
          className="page-title-action"
          href="?page=mailpoet-export"
        >
          {MailPoet.I18n.t('export')}
        </a>
      </h1>

      <SubscribersInPlan
        subscribersInPlan={window.mailpoet_subscribers_in_plan_count}
        subscribersInPlanLimit={window.mailpoet_subscribers_limit}
        mailpoetSubscribers={window.mailpoet_premium_subscribers_count}
        mailpoetSubscribersLimit={window.mailpoet_subscribers_limit}
        hasPremiumSupport={window.mailpoet_has_premium_support}
        wpUsersCount={window.mailpoet_wp_users_count}
        mssActive={window.mailpoet_mss_active}
      />

      <SubscribersLimitNotice />
      <InvalidMssKeyNotice
        mssKeyInvalid={window.mailpoet_mss_key_invalid}
        subscribersCount={window.mailpoet_subscribers_count}
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
      />
    </div>
  );
};

SubscriberList.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};

export default SubscriberList;
