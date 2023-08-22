import { Link, withRouter } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';

import { MailPoet } from 'mailpoet';
import { Listing } from 'listing/listing.jsx';
import { escapeHTML } from '@wordpress/escape-html';
import { SegmentResponse } from 'segments/types';
import { ListHeading } from 'segments/heading';
import * as ROUTES from 'segments/routes';
import { __, _n, sprintf } from '@wordpress/i18n';

type ColumnType = {
  name: string;
  label: string;
  sortable: boolean;
};

type DynamicSegmentItem = {
  id: number;
  name: string;
  description: string;
  count_all: string;
  count_subscribed: string;
  created_at: string;
  is_plugin_missing: boolean;
  subscribers_url: string;
  missing_plugin_message?: {
    message: string;
    link?: string;
  };
};

type DynamicSegmentListComponentProps = {
  location: {
    pathname: string;
  };
  match: {
    params;
  };
};

const columns: ColumnType[] = [
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
    const message = sprintf(
      // translators: %s is the number of segments.
      _n(
        '%s segment was moved to the trash.',
        '%s segments were moved to the trash.',
        count,
        'mailpoet',
      ),
      count.toLocaleString(),
    );
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    const message = sprintf(
      // translators: %s is the number of segments.
      _n(
        '%s segment was permanently deleted.',
        '%s segments were permanently deleted.',
        count,
        'mailpoet',
      ),
      count.toLocaleString(),
    );
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    const message = sprintf(
      // translators: %s is the number of segments.
      _n(
        '%s segment has been restored from the Trash.',
        '%s segments have been restored from the Trash.',
        count,
        'mailpoet',
      ),
      count.toLocaleString(),
    );
    MailPoet.Notice.success(message);
  },
};

const itemActions = [
  {
    name: 'edit',
    className: 'mailpoet-hide-on-mobile',
    link: (item: DynamicSegmentItem) => (
      <Link to={`${ROUTES.EDIT_DYNAMIC_SEGMENT}/${item.id}`}>
        {__('Edit', 'mailpoet')}
      </Link>
    ),
    display: (item: DynamicSegmentItem) => !item.is_plugin_missing,
  },
  {
    name: 'duplicate_segment',
    className: 'mailpoet-hide-on-mobile',
    label: __('Duplicate', 'mailpoet'),
    onClick: (item, refresh) =>
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'dynamic_segments',
        action: 'duplicate',
        data: {
          id: item.id,
        },
      })
        .done((response: SegmentResponse) => {
          MailPoet.Notice.success(
            // translators: %1$s is the name of the segments that was duplicated.
            __('Segment "%1$s" has been duplicated.', 'mailpoet').replace(
              '%1$s',
              escapeHTML(response.data.name),
            ),
            { scroll: true },
          );
          refresh();
        })
        .fail((response) => {
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true },
          );
        }),
  },
  {
    name: 'edit_disabled',
    className: 'mailpoet-hide-on-mobile mailpoet-disabled',
    link: (item: DynamicSegmentItem) => (
      <Link to={`${ROUTES.EDIT_DYNAMIC_SEGMENT}/${item.id}`}>
        {__('Edit', 'mailpoet')}
      </Link>
    ),
    display: (item: DynamicSegmentItem) => item.is_plugin_missing,
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

function renderItem(item: DynamicSegmentItem, actions) {
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
          colSpan={2}
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
            : item.missing_plugin_message.message}
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

function DynamicSegmentListComponent(
  props: DynamicSegmentListComponentProps,
): JSX.Element {
  return (
    <>
      <ListHeading segmentType="dynamic" />
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
    </>
  );
}

export const DynamicSegmentList = withRouter(DynamicSegmentListComponent);
