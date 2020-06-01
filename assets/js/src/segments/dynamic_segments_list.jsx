import React from 'react';
import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';
import Listing from 'listing/listing.jsx';
import PropTypes from 'prop-types';
import Tabs from './tabs';

import SubscribersInPlan from '../common/subscribers_in_plan';

const columns = [
  {
    name: 'name',
    label: MailPoet.I18n.t('nameColumn'),
    sortable: true,
  },
  {
    name: 'count',
    label: MailPoet.I18n.t('subscribersCountColumn'),
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
  },
];

function renderItem(item, actions) {
  return (
    <>
      <td data-colname={MailPoet.I18n.t('nameColumn')}>
        <strong>
          { item.name }
        </strong>
        { actions }
      </td>
      <td className="column" data-colname={MailPoet.I18n.t('subscribersCountColumn')}>
        { parseInt(item.count, 10).toLocaleString() }
      </td>
      <td className="column" data-colname={MailPoet.I18n.t('updatedAtColumn')}>
        { MailPoet.Date.format(item.updated_at) }
      </td>
    </>
  );
}

function DynamicSegmentList(props) {
  return (
    <>
      <h1 className="pageTitle">
        {MailPoet.I18n.t('pageTitleSegments')}
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

      <Tabs />

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

export default DynamicSegmentList;
