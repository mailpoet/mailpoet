import React from 'react';
import { Link, withRouter } from 'react-router-dom';
import MailPoet from 'mailpoet';
import Listing from 'listing/listing.jsx';
import PropTypes from 'prop-types';

const columns = [
  {
    name: 'name',
    label: MailPoet.I18n.t('nameColumn'),
    sortable: true,
  },
  {
    name: 'description',
    label: MailPoet.I18n.t('description'),
    sortable: false,
  },
  {
    name: 'count',
    label: MailPoet.I18n.t('subscribersCountColumn'),
    sortable: false,
  },
  {
    name: 'subscribed',
    label: MailPoet.I18n.t('subscribed'),
    sortable: false,
  },
  {
    name: 'updated_at',
    label: MailPoet.I18n.t('updatedAtColumn'),
    sortable: true,
  },
];

const messages = {
  onLoadingItems: () => MailPoet.I18n.t('loadingDynamicSegmentItems'),
  onNoItemsFound: () => MailPoet.I18n.t('noDynamicSegmentItemsFound'),
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneDynamicSegmentTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleDynamicSegmentsTrashed')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneDynamicSegmentDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleDynamicSegmentsDeleted')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneDynamicSegmentRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleDynamicSegmentsRestored')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
};

const itemActions = [
  {
    name: 'edit',
    className: 'mailpoet-hide-on-mobile',
    link: (item) => (
      <Link to={`/edit-segment/${item.id}`}>
        {MailPoet.I18n.t('edit')}
      </Link>
    ),
  },
  {
    name: 'view_subscribers',
    link: (item) => (
      <a href={item.subscribers_url}>
        {MailPoet.I18n.t('viewSubscribers')}
      </a>
    ),
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
  },
];

function renderItem(item, actions) {
  return (
    <>
      <td data-colname={MailPoet.I18n.t('nameColumn')}>
        <span className="mailpoet-listing-title">
          { item.name }
        </span>
        { actions }
      </td>
      <td data-colname={MailPoet.I18n.t('description')}>
        <abbr>{ item.description }</abbr>
      </td>
      <td className="column" data-colname={MailPoet.I18n.t('subscribersCountColumn')}>
        { parseInt(item.count_all, 10).toLocaleString() }
      </td>
      <td className="column" data-colname={MailPoet.I18n.t('subscribed')}>
        { parseInt(item.count_subscribed, 10).toLocaleString() }
      </td>
      <td className="column-date" data-colname={MailPoet.I18n.t('updatedAtColumn')}>
        { MailPoet.Date.short(item.created_at) }
        <br />
        { MailPoet.Date.time(item.created_at) }
      </td>
    </>
  );
}

function DynamicSegmentList(props) {
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
      />
      <p className="mailpoet_sending_methods_help help">
        <b>
          {MailPoet.I18n.t('segmentsTip')}
          :
        </b>
        {' '}
        {MailPoet.I18n.t('segmentsTipText')}
        {' '}
        <a
          href="https://kb.mailpoet.com/article/237-guide-to-subscriber-segmentation?utm_source=plugin&utm_medium=segments&utm_campaign=helpdocs"
          data-beacon-article="5a574bd92c7d3a194368233e"
          target="_blank"
          rel="noopener noreferrer"
        >
          {MailPoet.I18n.t('segmentsTipLink')}
        </a>
      </p>
    </>
  );
}

DynamicSegmentList.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};

export default withRouter(DynamicSegmentList);
