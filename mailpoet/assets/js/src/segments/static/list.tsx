import { Component, ReactNode } from 'react';
import { Link, withRouter, RouteComponentProps } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import classnames from 'classnames';
import { escapeHTML, escapeAttribute } from '@wordpress/escape-html';

import { Listing } from 'listing/listing.jsx';
import { SegmentResponse } from 'segments/types';
import { ListingsEngagementScore } from 'subscribers/listings-engagement-score';
import { ListHeading } from 'segments/heading';

type Segment = {
  type: string;
  id: number;
  name: string;
  description: string;
  average_engagement_score: number;
  subscribers_count: {
    subscribed?: number;
    unconfirmed?: number;
    unsubscribed?: number;
    inactive?: number;
    bounced?: number;
  };
  created_at: string;
  subscribers_url: string;
};

const isWPUsersSegment = (segment: Segment) => segment.type === 'wp_users';
const isWooCommerceCustomersSegment = (segment: Segment) =>
  segment.type === 'woocommerce_users';
const isSpecialSegment = (segment: Segment) =>
  isWPUsersSegment(segment) || isWooCommerceCustomersSegment(segment);
const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

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
    name: 'average_subscriber_score',
    label: MailPoet.I18n.t('listScore'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'subscribed',
    label: MailPoet.I18n.t('subscribed'),
    className: 'mailpoet-listing-column-narrow',
  },
  {
    name: 'unconfirmed',
    label: MailPoet.I18n.t('unconfirmed'),
    className: 'mailpoet-listing-column-narrow',
  },
  {
    name: 'unsubscribed',
    label: MailPoet.I18n.t('unsubscribed'),
    className: 'mailpoet-listing-column-narrow',
  },
  {
    name: 'inactive',
    label: MailPoet.I18n.t('inactive'),
    className: 'mailpoet-listing-column-narrow',
  },
  {
    name: 'bounced',
    label: MailPoet.I18n.t('bounced'),
    className: 'mailpoet-listing-column-narrow',
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
      message = MailPoet.I18n.t('oneSegmentTrashed');
    } else {
      message = MailPoet.I18n.t('multipleSegmentsTrashed').replace(
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
      message = MailPoet.I18n.t('oneSegmentDeleted');
    } else {
      message = MailPoet.I18n.t('multipleSegmentsDeleted').replace(
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
      message = MailPoet.I18n.t('oneSegmentRestored');
    } else {
      message = MailPoet.I18n.t('multipleSegmentsRestored').replace(
        '%1$d',
        count.toLocaleString(),
      );
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

const isItemDeletable = (segment: Segment) => {
  const isDeletable = !isSpecialSegment(segment);
  return isDeletable;
};

const itemActions = [
  {
    name: 'edit',
    className: 'mailpoet-hide-on-mobile',
    link: function link(item: Segment) {
      return <Link to={`/edit/${item.id}`}>{MailPoet.I18n.t('edit')}</Link>;
    },
    display: function display(segment: Segment) {
      return !isSpecialSegment(segment);
    },
  },
  {
    name: 'duplicate_segment',
    className: 'mailpoet-hide-on-mobile',
    label: MailPoet.I18n.t('duplicate'),
    onClick: (item, refresh) =>
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
        action: 'duplicate',
        data: {
          id: item.id,
        },
      })
        .done((response: SegmentResponse) => {
          MailPoet.Notice.success(
            MailPoet.I18n.t('listDuplicated').replace(
              '%1$s',
              escapeHTML(response.data.name),
            ),
          );
          refresh();
        })
        .fail((response) => {
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true },
          );
        }),
    display: function display(segment: Segment) {
      return !isSpecialSegment(segment);
    },
  },
  {
    name: 'read_more',
    className: 'mailpoet-hide-on-mobile',
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
    display: function display(segment: Segment) {
      return isWPUsersSegment(segment);
    },
  },
  {
    name: 'synchronize_segment',
    label: MailPoet.I18n.t('forceSync'),
    onClick: async function onClick(item: Segment, refresh) {
      MailPoet.Modal.loading(true);
      await MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
        action: 'synchronize',
        data: {
          type: item.type,
        },
      })
        .done(() => {
          let message = MailPoet.I18n.t('listSynchronized').replace(
            '%1$s',
            item.name,
          );
          if (item.type === 'woocommerce_users') {
            message = MailPoet.I18n.t(
              'listSynchronizationWasScheduled',
            ).replace('%1$s', item.name);
          }
          MailPoet.Modal.loading(false);
          MailPoet.Notice.success(message);
          refresh();
        })
        .fail((response) => {
          MailPoet.Modal.loading(false);
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map((error) => error.message),
              { scroll: true },
            );
          }
        });
    },
    display: function display(segment: Segment) {
      return (
        isWPUsersSegment(segment) || isWooCommerceCustomersSegment(segment)
      );
    },
  },
  {
    name: 'view_subscribers',
    link: function link(item: Segment) {
      return (
        <a
          href={item.subscribers_url}
          data-automation-id={`view_subscribers_${item.name}`}
        >
          {MailPoet.I18n.t('viewSubscribers')}
        </a>
      );
    },
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
    display: function display(segment: Segment) {
      return !isWooCommerceCustomersSegment(segment);
    },
  },
];

class SegmentListComponent extends Component<RouteComponentProps> {
  renderItem = (segment: Segment, actions: ReactNode) => {
    const rowClasses = classnames(
      'manage-column',
      'column-primary',
      'has-row-actions',
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
        <span className="mailpoet-listing-title">
          {escapeHTML(segment.name)}
        </span>
      );
    } else {
      segmentName = (
        <Link className="mailpoet-listing-title" to={`/edit/${segment.id}`}>
          {escapeHTML(segment.name)}
        </Link>
      );
    }

    return (
      <div>
        <td
          className={rowClasses}
          data-automation-id={`segment_name_${escapeAttribute(segment.name)}`}
        >
          {segmentName}
          {actions}
        </td>
        <td data-colname={MailPoet.I18n.t('description')}>
          <abbr>{escapeHTML(segment.description)}</abbr>
        </td>
        {mailpoetTrackingEnabled ? (
          <td
            className="column mailpoet-listing-stats-column"
            data-colname={MailPoet.I18n.t('averageScore')}
          >
            <div className="mailpoet-listing-stats">
              <ListingsEngagementScore
                id={segment.id}
                engagementScore={segment.average_engagement_score}
              />
            </div>
          </td>
        ) : null}
        <td
          className="mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('subscribed')}
        >
          <abbr>{subscribed.toLocaleString()}</abbr>
        </td>
        <td
          className="mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('unconfirmed')}
        >
          <abbr>{unconfirmed.toLocaleString()}</abbr>
        </td>
        <td
          className="mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('unsubscribed')}
        >
          <abbr>{unsubscribed.toLocaleString()}</abbr>
        </td>
        <td
          className="mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('inactive')}
        >
          <abbr>{inactive.toLocaleString()}</abbr>
        </td>
        <td
          className="mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('bounced')}
        >
          <abbr>{bounced.toLocaleString()}</abbr>
        </td>
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('createdOn')}
        >
          {MailPoet.Date.short(segment.created_at)}
          <br />
          {MailPoet.Date.time(segment.created_at)}
        </td>
      </div>
    );
  };

  render() {
    return (
      <>
        <ListHeading segmentType="static" />
        <div className="mailpoet-segments-listing">
          <Listing
            limit={window.mailpoet_listing_per_page}
            location={this.props.location}
            params={this.props.match.params}
            messages={messages}
            search={false}
            endpoint="segments"
            base_url="lists"
            onRenderItem={this.renderItem}
            columns={columns}
            bulk_actions={bulkActions}
            item_actions={itemActions}
            sort_by="name"
            sort_order="asc"
            isItemDeletable={isItemDeletable}
            isItemToggleable={isWPUsersSegment}
          />
        </div>
      </>
    );
  }
}

export const SegmentList = withRouter(SegmentListComponent);
