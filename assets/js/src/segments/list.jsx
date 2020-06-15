import React from 'react';
import { Link, withRouter } from 'react-router-dom';
import MailPoet from 'mailpoet';
import classNames from 'classnames';
import PropTypes from 'prop-types';

import Listing from 'listing/listing.jsx';
import SubscribersLimitNotice from 'notices/subscribers_limit_notice.jsx';
import InvalidMssKeyNotice from '../notices/invalid_mss_key_notice';
import SubscribersInPlan from '../common/subscribers_in_plan';

const isWPUsersSegment = (segment) => segment.type === 'wp_users';
const isWooCommerceCustomersSegment = (segment) => segment.type === 'woocommerce_users';
const isSpecialSegment = (segmt) => isWPUsersSegment(segmt) || isWooCommerceCustomersSegment(segmt);

const columns = [
  {
    name: 'name',
    label: MailPoet.I18n.t('name'),
    sortable: true,
  },
  {
    name: 'description',
    label: MailPoet.I18n.t('description'),
  },
  {
    name: 'subscribed',
    label: MailPoet.I18n.t('subscribed'),
  },
  {
    name: 'unconfirmed',
    label: MailPoet.I18n.t('unconfirmed'),
  },
  {
    name: 'unsubscribed',
    label: MailPoet.I18n.t('unsubscribed'),
  },
  {
    name: 'inactive',
    label: MailPoet.I18n.t('inactive'),
  },
  {
    name: 'bounced',
    label: MailPoet.I18n.t('bounced'),
  },
  {
    name: 'created_at',
    label: MailPoet.I18n.t('createdOn'),
    sortable: true,
  },
];

const messages = {
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneSegmentTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleSegmentsTrashed')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneSegmentDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleSegmentsDeleted')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneSegmentRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleSegmentsRestored')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
};

const bulkActions = [
  {
    name: 'trash',
    label: MailPoet.I18n.t('moveToTrash'),
    onSuccess: messages.onTrash,
  },
];

const itemActions = [
  {
    name: 'edit',
    link: function link(item) {
      return (
        <Link to={`/edit/${item.id}`}>{MailPoet.I18n.t('edit')}</Link>
      );
    },
    display: function display(segment) {
      return !isSpecialSegment(segment);
    },
  },
  {
    name: 'duplicate_segment',
    label: MailPoet.I18n.t('duplicate'),
    onClick: (item, refresh) => MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'segments',
      action: 'duplicate',
      data: {
        id: item.id,
      },
    }).done((response) => {
      MailPoet.Notice.success(
        MailPoet.I18n.t('listDuplicated').replace('%$1s', response.data.name)
      );
      refresh();
    }).fail((response) => {
      MailPoet.Notice.error(
        response.errors.map((error) => error.message),
        { scroll: true }
      );
    }),
    display: function display(segment) {
      return !isSpecialSegment(segment);
    },
  },
  {
    name: 'read_more',
    link: function link() {
      return (
        <a
          href="https://kb.mailpoet.com/article/133-the-wordpress-users-list"
          target="_blank"
          rel="noopener noreferrer"
        >
          {MailPoet.I18n.t('readMore')}
        </a>
      );
    },
    display: function display(segment) {
      return isWPUsersSegment(segment);
    },
  },
  {
    name: 'synchronize_segment',
    label: MailPoet.I18n.t('forceSync'),
    onClick: function onClick(item, refresh) {
      MailPoet.Modal.loading(true);
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
        action: 'synchronize',
        data: {
          type: item.type,
        },
      }).done(() => {
        MailPoet.Modal.loading(false);
        MailPoet.Notice.success(
          (MailPoet.I18n.t('listSynchronized')).replace('%$1s', item.name)
        );
        refresh();
      }).fail((response) => {
        MailPoet.Modal.loading(false);
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true }
          );
        }
      });
    },
    display: function display(segment) {
      return isWPUsersSegment(segment) || isWooCommerceCustomersSegment(segment);
    },
  },
  {
    name: 'view_subscribers',
    link: function link(item) {
      return (
        <a href={item.subscribers_url} data-automation-id={`view_subscribers_${item.name}`}>
          {MailPoet.I18n.t('viewSubscribers')}
        </a>
      );
    },
  },
  {
    name: 'trash',
    display: function display(segment) {
      return !isSpecialSegment(segment) && segment.automated_emails_subjects.length === 0;
    },
  },
  {
    name: 'delete',
    label: MailPoet.I18n.t('moveToTrash'),
    onClick: function onClick(segment) {
      MailPoet.Notice.error(
        MailPoet.I18n.t('trashDisallowed').replace(
          '%$1s',
          segment.automated_emails_subjects.map((subject) => `'${subject}'`).join(', ')
        ),
        { scroll: true }
      );
    },
    display: function display(segment) {
      return !isSpecialSegment(segment) && segment.automated_emails_subjects.length > 0;
    },
  },
];

class SegmentList extends React.Component {
  renderItem = (segment, actions) => {
    const rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    const subscribed = Number(segment.subscribers_count.subscribed || 0);
    const unconfirmed = Number(segment.subscribers_count.unconfirmed || 0);
    const unsubscribed = Number(segment.subscribers_count.unsubscribed || 0);
    const inactive = Number(segment.subscribers_count.inactive || 0);
    const bounced = Number(segment.subscribers_count.bounced || 0);

    let segmentName;

    if (isSpecialSegment(segment)) {
      // the WP users and WooCommerce customers segments
      // are not editable so just display their names
      segmentName = (
        <span className="row-title">{ segment.name }</span>
      );
    } else {
      segmentName = (
        <Link
          className="row-title"
          to={`/edit/${segment.id}`}
        >
          { segment.name }
        </Link>
      );
    }

    return (
      <div>
        <td className={rowClasses} data-automation-id={`segment_name_${segment.name}`}>
          <strong>
            { segmentName }
          </strong>
          { actions }
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('description')}>
          <abbr>{ segment.description }</abbr>
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('subscribed')}>
          <abbr>{ subscribed.toLocaleString() }</abbr>
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('unconfirmed')}>
          <abbr>{ unconfirmed.toLocaleString() }</abbr>
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('unsubscribed')}>
          <abbr>{ unsubscribed.toLocaleString() }</abbr>
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('inactive')}>
          <abbr>{ inactive.toLocaleString() }</abbr>
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('bounced')}>
          <abbr>{ bounced.toLocaleString() }</abbr>
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('createdOn')}>
          <abbr>{ MailPoet.Date.format(segment.created_at) }</abbr>
        </td>
      </div>
    );
  };

  render() {
    return (
      <div className="mailpoet-segments-listing">
        <h1 className="title">
          {MailPoet.I18n.t('pageTitle')}
          {' '}
          <Link className="page-title-action" to="/new">{MailPoet.I18n.t('new')}</Link>
          <Link className="page-title-action" to="/new-segment" data-automation-id="new-segment">{MailPoet.I18n.t('newSegment')}</Link>
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
          location={this.props.location}
          params={this.props.match.params}
          messages={messages}
          search={false}
          endpoint="segments"
          onRenderItem={this.renderItem}
          columns={columns}
          bulk_actions={bulkActions}
          item_actions={itemActions}
          sort_by="name"
          sort_order="asc"
        />
      </div>
    );
  }
}

SegmentList.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};

export default withRouter(SegmentList);
