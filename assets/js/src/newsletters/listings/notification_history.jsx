import React from 'react'
import { Router, Link } from 'react-router'
import classNames from 'classnames'
import jQuery from 'jquery'
import MailPoet from 'mailpoet'

import Listing from 'listing/listing.jsx'
import ListingTabs from 'newsletters/listings/tabs.jsx'

import { QueueMixin, StatisticsMixin } from 'newsletters/listings/mixins.jsx'

const mailpoet_tracking_enabled = (!!(window['mailpoet_tracking_enabled']));

const columns = [
  {
    name: 'subject',
    label: MailPoet.I18n.t('subject'),
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status')
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('lists')
  },
  {
    name: 'statistics',
    label: MailPoet.I18n.t('statistics'),
    display: mailpoet_tracking_enabled
  },
  {
    name: 'processed_at',
    label: MailPoet.I18n.t('sentOn'),
  }
];

const newsletter_actions = [
  {
    name: 'view',
    link: function(newsletter) {
      return (
        <a href={ newsletter.preview_url } target="_blank">
          {MailPoet.I18n.t('preview')}
        </a>
      );
    }
  }
];

const NewsletterListNotificationHistory = React.createClass({
  mixins: [QueueMixin, StatisticsMixin],
  renderItem: function(newsletter, actions) {
    const rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    const segments = newsletter.segments.map(function(segment) {
      return segment.name
    }).join(', ');

    return (
      <div>
        <td className={ rowClasses }>
          <strong>
            <a
              href={ newsletter.preview_url }
              target="_blank"
            >{ newsletter.subject }</a>
          </strong>
          { actions }
        </td>
        <td className="column" data-colname={ MailPoet.I18n.t('status') }>
          { this.renderQueueStatus(newsletter) }
        </td>
        <td className="column" data-colname={ MailPoet.I18n.t('lists') }>
          { segments }
        </td>
        { (mailpoet_tracking_enabled === true) ? (
          <td className="column" data-colname={ MailPoet.I18n.t('statistics') }>
            { this.renderStatistics(newsletter) }
          </td>
        ) : null }
        <td className="column-date" data-colname={ MailPoet.I18n.t('lastModifiedOn') }>
          <abbr>{ MailPoet.Date.format(newsletter.updated_at) }</abbr>
        </td>
      </div>
    );
  },
  render: function() {
    return (
      <div>
        <h1 className="title">
          {MailPoet.I18n.t('pageTitle')} <Link className="page-title-action" to="/new">{MailPoet.I18n.t('new')}</Link>
        </h1>

        <ListingTabs tab="notification" />

        <Link
          className="page-title-action"
          to="/notification"
        >{MailPoet.I18n.t('backToPostNotifications')}</Link>

        <Listing
          limit={ mailpoet_listing_per_page }
          location={ this.props.location }
          params={ this.props.params }
          endpoint="newsletters"
          type="notification_history"
          base_url="notification/history/:parent_id"
          onRenderItem={ this.renderItem }
          columns={columns}
          item_actions={ newsletter_actions }
          auto_refresh={ true }
          sort_by="updated_at"
          sort_order="desc"
        />
      </div>
    );
  }
});

module.exports = NewsletterListNotificationHistory;