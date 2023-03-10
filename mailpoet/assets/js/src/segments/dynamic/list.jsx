import { Link, withRouter } from 'react-router-dom';
import PropTypes from 'prop-types';
import ReactStringReplace from 'react-string-replace';
import { __, _x } from '@wordpress/i18n';

import { MailPoet } from 'mailpoet';
import { Listing } from 'listing/listing.jsx';

const columns = [
  {
    name: 'name',
    label: __('Name', 'mailpoet'),
    sortable: true,
  },
  {
    name: 'description',
    label: __('Description', 'mailpoet'),
    sortable: false,
  },
  {
    name: 'count',
    label: __('Number of subscribers', 'mailpoet'),
    sortable: false,
  },
  {
    name: 'subscribed',
    label: __('Subscribed', 'mailpoet'),
    sortable: false,
  },
  {
    name: 'updated_at',
    label: __('Modified on', 'mailpoet'),
    sortable: true,
  },
];

const messages = {
  onLoadingItems: () => __('Loading data…', 'mailpoet'),
  onNoItemsFound: () => __('No segments found', 'mailpoet'),
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 segment was moved to the trash.', 'mailpoet');
    } else {
      message = __(
        '%1$d segments were moved to the trash.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 segment was permanently deleted.', 'mailpoet');
    } else {
      message = __(
        '%1$d segments were permanently deleted.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 segment has been restored from the Trash.', 'mailpoet');
    } else {
      message = __(
        '%1$d segments have been restored from the Trash.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
};

const itemActions = [
  {
    name: 'edit',
    className: 'mailpoet-hide-on-mobile',
    link: (item) => (
      <Link to={`/edit-segment/${item.id}`}>{__('Edit', 'mailpoet')}</Link>
    ),
    display: (item) => !item.is_plugin_missing,
  },
  {
    name: 'edit_disabled',
    className: 'mailpoet-hide-on-mobile mailpoet-disabled',
    link: (item) => (
      <Link to={`/edit-segment/${item.id}`}>{__('Edit', 'mailpoet')}</Link>
    ),
    display: (item) => item.is_plugin_missing,
  },
  {
    name: 'view_subscribers',
    link: (item) => (
      <a href={item.subscribers_url}>{__('View Subscribers', 'mailpoet')}</a>
    ),
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
  },
];

const bulkActions = [
  {
    name: 'trash',
    label: __('Move to trash', 'mailpoet'),
    onSuccess: messages.onTrash,
  },
];

function renderItem(item, actions) {
  return (
    <>
      <td className="column-primary" data-colname={__('Name', 'mailpoet')}>
        <span className="mailpoet-listing-title">{item.name}</span>
        {actions}
      </td>
      <td data-colname={__('Description', 'mailpoet')}>
        <abbr>{item.description}</abbr>
      </td>
      {item.is_plugin_missing ? (
        <td
          colSpan="2"
          className="column mailpoet-hide-on-mobile"
          data-colname={__('Missing plugin message', 'mailpoet')}
        >
          {item.missing_plugin_message &&
          item.missing_plugin_message.message &&
          item.missing_plugin_message.link
            ? ReactStringReplace(
                item.missing_plugin_message.message,
                /\[link](.*?)\[\/link]/g,
                (match) => (
                  <a
                    className="mailpoet-listing-link-important"
                    key="missingPluginMessageLink"
                    href={item.missing_plugin_message.link}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {match}
                  </a>
                ),
              )
            : item.missing_plugin_message}
        </td>
      ) : (
        <>
          <td
            className="column mailpoet-hide-on-mobile"
            data-colname={__('Number of subscribers', 'mailpoet')}
          >
            {parseInt(item.count_all, 10).toLocaleString()}
          </td>
          <td
            className="column mailpoet-hide-on-mobile"
            data-colname={__('Subscribed', 'mailpoet')}
          >
            {parseInt(item.count_subscribed, 10).toLocaleString()}
          </td>
        </>
      )}
      <td
        className="column-date mailpoet-hide-on-mobile"
        data-colname={__('Modified on', 'mailpoet')}
      >
        {MailPoet.Date.short(item.created_at)}
        <br />
        {MailPoet.Date.time(item.created_at)}
      </td>
    </>
  );
}

function DynamicSegmentListComponent(props) {
  return (
    <>
      <Listing
        limit={window.mailpoet_listing_per_page}
        location={props.location}
        params={props.match.params}
        search
        onRenderItem={renderItem}
        endpoint="dynamic_segments"
        base_url="segments"
        columns={columns}
        messages={messages}
        sort_by="created_at"
        sort_order="desc"
        item_actions={itemActions}
        bulk_actions={bulkActions}
      />
      <p className="mailpoet_sending_methods_help help">
        <b>{_x('Tip', 'A note about dynamic segments usage', 'mailpoet')}:</b>{' '}
        {__(
          'segments allow you to group your subscribers by other criteria, such as events and actions.',
          'mailpoet',
        )}{' '}
        <a
          href="https://kb.mailpoet.com/article/237-guide-to-subscriber-segmentation?utm_source=plugin&utm_medium=segments&utm_campaign=helpdocs"
          data-beacon-article="5a574bd92c7d3a194368233e"
          target="_blank"
          rel="noopener noreferrer"
        >
          {__('Read more.', 'mailpoet')}
        </a>
      </p>
    </>
  );
}

DynamicSegmentListComponent.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};

DynamicSegmentListComponent.displayName = 'DynamicSegmentList';

export const DynamicSegmentList = withRouter(DynamicSegmentListComponent);
