import React from 'react'
import { Router, Link } from 'react-router'
import classNames from 'classnames'
import jQuery from 'jquery'
import MailPoet from 'mailpoet'

import Listing from 'listing/listing.jsx'
import ListingTabs from 'newsletters/listings/tabs.jsx'

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
  pauseSending: function(newsletter) {
    MailPoet.Ajax.post({
      endpoint: 'sendingQueue',
      action: 'pause',
      data: newsletter.id
    }).done(function() {
      jQuery('#resume_'+newsletter.id).show();
      jQuery('#pause_'+newsletter.id).hide();
    });
  },
  resumeSending: function(newsletter) {
    MailPoet.Ajax.post({
      endpoint: 'sendingQueue',
      action: 'resume',
      data: newsletter.id
    }).done(function() {
      jQuery('#pause_'+newsletter.id).show();
      jQuery('#resume_'+newsletter.id).hide();
    });
  },
  renderStatus: function(newsletter) {
    if (!newsletter.queue) {
      return (
        <span>{MailPoet.I18n.t('notSentYet')}</span>
      );
    } else {
      if (newsletter.queue.status === 'scheduled') {
        return (
          <span>{MailPoet.I18n.t('scheduledFor')}  { MailPoet.Date.format(newsletter.queue.scheduled_at) } </span>
        )
      }
      const progressClasses = classNames(
        'mailpoet_progress',
        { 'mailpoet_progress_complete': newsletter.queue.status === 'completed'}
      );

      // calculate percentage done
      const percentage = Math.round(
        (newsletter.queue.count_processed * 100) / (newsletter.queue.count_total)
      );

      let label;

      if (newsletter.queue.status === 'completed') {
        label = (
          <span>
            {
              MailPoet.I18n.t('newsletterQueueCompleted')
              .replace("%$1d", newsletter.queue.count_processed - newsletter.queue.count_failed)
              .replace("%$2d", newsletter.queue.count_total)
            }
          </span>
        );
      } else {
        label = (
          <span>
            { newsletter.queue.count_processed } / { newsletter.queue.count_total }
            &nbsp;&nbsp;
            <a
              id={ 'resume_'+newsletter.id }
              className="button"
              style={{ display: (newsletter.queue.status === 'paused') ? 'inline-block': 'none' }}
              href="javascript:;"
              onClick={ this.resumeSending.bind(null, newsletter) }
            >{MailPoet.I18n.t('resume')}</a>
            <a
              id={ 'pause_'+newsletter.id }
              className="button mailpoet_pause"
              style={{ display: (newsletter.queue.status === null) ? 'inline-block': 'none' }}
              href="javascript:;"
              onClick={ this.pauseSending.bind(null, newsletter) }
            >{MailPoet.I18n.t('pause')}</a>
          </span>
        );
      }

      return (
        <div>
          <div className={ progressClasses }>
              <span
                className="mailpoet_progress_bar"
                style={ { width: percentage + "%"} }
              ></span>
              <span className="mailpoet_progress_label">
                { percentage + "%" }
              </span>
          </div>
          <p style={{ textAlign:'center' }}>
            { label }
          </p>
        </div>
      );
    }
  },
  renderStatistics: function(newsletter) {
    if (mailpoet_tracking_enabled === false) {
      return;
    }

    if (newsletter.statistics && newsletter.queue && newsletter.queue.status !== 'scheduled') {
      const total_sent = ~~(newsletter.queue.count_processed);

      let percentage_clicked = 0;
      let percentage_opened = 0;
      let percentage_unsubscribed = 0;

      if (total_sent > 0) {
        percentage_clicked = Math.round(
          (~~(newsletter.statistics.clicked) * 100) / total_sent
        );
        percentage_opened = Math.round(
          (~~(newsletter.statistics.opened) * 100) / total_sent
        );
        percentage_unsubscribed = Math.round(
          (~~(newsletter.statistics.unsubscribed) * 100) / total_sent
        );
      }

      return (
        <span>
          { percentage_opened }%, { percentage_clicked }%, { percentage_unsubscribed }%
        </span>
      );
    } else {
      return (
        <span>{MailPoet.I18n.t('notSentYet')}</span>
      );
    }
  },
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
          { this.renderStatus(newsletter) }
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