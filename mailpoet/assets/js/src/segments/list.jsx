import { Component } from 'react';
import { __ } from '@wordpress/i18n';
import { Link, withRouter } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import classnames from 'classnames';
import PropTypes from 'prop-types';

import { Listing } from 'listing/listing.jsx';
import { ListingsEngagementScore } from '../subscribers/listings_engagement_score';

const isWPUsersSegment = (segment) => segment.type === 'wp_users';
const isWooCommerceCustomersSegment = (segment) =>
  segment.type === 'woocommerce_users';
const isSpecialSegment = (segmt) =>
  isWPUsersSegment(segmt) || isWooCommerceCustomersSegment(segmt);
const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

const columns = [
  {
    name: 'name',
    label: __('Name', 'mailpoet'),
    sortable: true,
  },
  {
    name: 'description',
    label: __('Description', 'mailpoet'),
  },
  {
    name: 'average_subscriber_score',
    label: __('List score', 'mailpoet'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'subscribed',
    label: __('Subscribed', 'mailpoet'),
    className: 'mailpoet-listing-column-narrow',
  },
  {
    name: 'unconfirmed',
    label: __('Unconfirmed', 'mailpoet'),
    className: 'mailpoet-listing-column-narrow',
  },
  {
    name: 'unsubscribed',
    label: __('Unsubscribed', 'mailpoet'),
    className: 'mailpoet-listing-column-narrow',
  },
  {
    name: 'inactive',
    label: __('Inactive', 'mailpoet'),
    className: 'mailpoet-listing-column-narrow',
  },
  {
    name: 'bounced',
    label: __('Bounced', 'mailpoet'),
    className: 'mailpoet-listing-column-narrow',
  },
  {
    name: 'created_at',
    label: __('Created on', 'mailpoet'),
    sortable: true,
  },
];

const messages = {
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __(
        '1 list was moved to the trash. Note that deleting a list does not delete its subscribers.',
        'mailpoet',
      );
    } else {
      message = __(
        '%1$d lists were moved to the trash. Note that deleting a list does not delete its subscribers.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __(
        '1 list was permanently deleted. Note that deleting a list does not delete its subscribers.',
        'mailpoet',
      );
    } else {
      message = __(
        '%1$d lists were permanently deleted. Note that deleting a list does not delete its subscribers.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 list has been restored from the Trash.', 'mailpoet');
    } else {
      message = __(
        '%1$d lists have been restored from the Trash.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
};

const bulkActions = [
  {
    name: 'trash',
    label: __('Move to trash', 'mailpoet'),
    onSuccess: messages.onTrash,
  },
];

const isItemDeletable = (segment) => {
  const isDeletable = !isSpecialSegment(segment);
  return isDeletable;
};

const itemActions = [
  {
    name: 'edit',
    className: 'mailpoet-hide-on-mobile',
    link: function link(item) {
      return <Link to={`/edit/${item.id}`}>{__('Edit', 'mailpoet')}</Link>;
    },
    display: function display(segment) {
      return !isSpecialSegment(segment);
    },
  },
  {
    name: 'duplicate_segment',
    className: 'mailpoet-hide-on-mobile',
    label: __('Duplicate', 'mailpoet'),
    onClick: (item, refresh) =>
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
        action: 'duplicate',
        data: {
          id: item.id,
        },
      })
        .done((response) => {
          MailPoet.Notice.success(
            __('List "%1$s" has been duplicated.', 'mailpoet').replace(
              '%1$s',
              response.data.name,
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
    display: function display(segment) {
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
          {__('Read More', 'mailpoet')}
        </a>
      );
    },
    display: function display(segment) {
      return isWPUsersSegment(segment);
    },
  },
  {
    name: 'synchronize_segment',
    label: __('Force Sync', 'mailpoet'),
    onClick: function onClick(item, refresh) {
      MailPoet.Modal.loading(true);
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
        action: 'synchronize',
        data: {
          type: item.type,
        },
      })
        .done(() => {
          let message = __(
            'List "%1$s" has been synchronized.',
            'mailpoet',
          ).replace('%1$s', item.name);
          if (item.type === 'woocommerce_users') {
            message = __(
              'Synchronization of the "%1$s" list started. It can take several minutes to finish.',
              'mailpoet',
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
    display: function display(segment) {
      return (
        isWPUsersSegment(segment) || isWooCommerceCustomersSegment(segment)
      );
    },
  },
  {
    name: 'view_subscribers',
    link: function link(item) {
      return (
        <a
          href={item.subscribers_url}
          data-automation-id={`view_subscribers_${item.name}`}
        >
          {__('View Subscribers', 'mailpoet')}
        </a>
      );
    },
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
    display: function display(segmt) {
      return !isWooCommerceCustomersSegment(segmt);
    },
  },
];

class SegmentListComponent extends Component {
  renderItem = (segment, actions) => {
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
        <span className="mailpoet-listing-title">{segment.name}</span>
      );
    } else {
      segmentName = (
        <Link className="mailpoet-listing-title" to={`/edit/${segment.id}`}>
          {segment.name}
        </Link>
      );
    }

    return (
      <div>
        <td
          className={rowClasses}
          data-automation-id={`segment_name_${segment.name}`}
        >
          {segmentName}
          {actions}
        </td>
        <td data-colname={__('Description', 'mailpoet')}>
          <abbr>{segment.description}</abbr>
        </td>
        {mailpoetTrackingEnabled ? (
          <td
            className="column mailpoet-listing-stats-column"
            data-colname={__('Score', 'mailpoet')}
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
          data-colname={__('Subscribed', 'mailpoet')}
        >
          <abbr>{subscribed.toLocaleString()}</abbr>
        </td>
        <td
          className="mailpoet-hide-on-mobile"
          data-colname={__('Unconfirmed', 'mailpoet')}
        >
          <abbr>{unconfirmed.toLocaleString()}</abbr>
        </td>
        <td
          className="mailpoet-hide-on-mobile"
          data-colname={__('Unsubscribed', 'mailpoet')}
        >
          <abbr>{unsubscribed.toLocaleString()}</abbr>
        </td>
        <td
          className="mailpoet-hide-on-mobile"
          data-colname={__('Inactive', 'mailpoet')}
        >
          <abbr>{inactive.toLocaleString()}</abbr>
        </td>
        <td
          className="mailpoet-hide-on-mobile"
          data-colname={__('Bounced', 'mailpoet')}
        >
          <abbr>{bounced.toLocaleString()}</abbr>
        </td>
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={__('Created on', 'mailpoet')}
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
    );
  }
}

SegmentListComponent.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};

SegmentListComponent.displayName = 'SegmentList';

export const SegmentList = withRouter(SegmentListComponent);
