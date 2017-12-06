import React from 'react';
import { Link } from 'react-router';

import Listing from 'listing/listing.jsx';
import ListingTabs from 'newsletters/listings/tabs.jsx';

import { StatisticsMixin, MailerMixin } from 'newsletters/listings/mixins.jsx';

import classNames from 'classnames';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import Hooks from 'wp-js-hooks';

const mailpoet_roles = window.mailpoet_roles || {};
const mailpoet_segments = window.mailpoet_segments || {};
const mailpoet_tracking_enabled = (!!(window.mailpoet_tracking_enabled));

const messages = {
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersTrashed')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersDeleted')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersRestored')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
};

const columns = [
  {
    name: 'subject',
    label: MailPoet.I18n.t('subject'),
    sortable: true,
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
    width: 145,
  },
  {
    name: 'settings',
    label: MailPoet.I18n.t('settings'),
  },
  {
    name: 'statistics',
    label: MailPoet.I18n.t('statistics'),
    display: mailpoet_tracking_enabled,
  },
  {
    name: 'updated_at',
    label: MailPoet.I18n.t('lastModifiedOn'),
    sortable: true,
  },
];

const bulk_actions = [
  {
    name: 'trash',
    label: MailPoet.I18n.t('moveToTrash'),
    onSuccess: messages.onTrash,
  },
];

let newsletter_actions = [
  {
    name: 'view',
    link: function (newsletter) {
      return (
        <a href={newsletter.preview_url} target="_blank">
          {MailPoet.I18n.t('preview')}
        </a>
      );
    },
  },
  {
    name: 'edit',
    link: function (newsletter) {
      return (
        <a href={`?page=mailpoet-newsletter-editor&id=${newsletter.id}`}>
          {MailPoet.I18n.t('edit')}
        </a>
      );
    },
  },
  {
    name: 'trash',
  },
];

Hooks.addFilter('mailpoet_newsletters_listings_welcome_notification_actions', StatisticsMixin.addStatsCTAAction);
newsletter_actions = Hooks.applyFilters('mailpoet_newsletters_listings_welcome_notification_actions', newsletter_actions);

const NewsletterListWelcome = React.createClass({
  mixins: [StatisticsMixin, MailerMixin],
  updateStatus: function (e) {
    // make the event persist so that we can still override the selected value
    // in the ajax callback
    e.persist();

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'setStatus',
      data: {
        id: Number(e.target.getAttribute('data-id')),
        status: e.target.value,
      },
    }).done((response) => {
      if (response.data.status === 'active') {
        MailPoet.Notice.success(MailPoet.I18n.t('welcomeEmailActivated'));
      }
      // force refresh of listing so that groups are updated
      this.forceUpdate();
    }).fail((response) => {
      MailPoet.Notice.error(MailPoet.I18n.t('welcomeEmailActivationFailed'));

      // reset value to actual newsletter's status
      e.target.value = response.status;
    });
  },
  renderStatus: function (newsletter) {
    const total_sent = (
      MailPoet.I18n.t('sentToXSubscribers')
      .replace('%$1d', newsletter.total_sent.toLocaleString())
    );

    return (
      <div>
        <p>
          <select
            data-id={newsletter.id}
            defaultValue={newsletter.status}
            onChange={this.updateStatus}
          >
            <option value="active">{ MailPoet.I18n.t('active') }</option>
            <option value="draft">{ MailPoet.I18n.t('inactive') }</option>
          </select>
        </p>
        <p>{ total_sent }</p>
      </div>
    );
  },
  renderSettings: function (newsletter) {
    let sendingEvent;
    let sendingDelay;
    let segment;

    // set sending event
    switch (newsletter.options.event) {
      case 'user':
        // WP User
        if (newsletter.options.role === 'mailpoet_all') {
          sendingEvent = MailPoet.I18n.t('welcomeEventWPUserAnyRole');
        } else {
          sendingEvent = MailPoet.I18n.t('welcomeEventWPUserWithRole').replace(
            '%$1s', mailpoet_roles[newsletter.options.role]
          );
        }
        break;

      case 'segment':
        // get segment
        segment = _.find(mailpoet_segments, (seg) => {
          return (Number(seg.id) === Number(newsletter.options.segment));
        });

        if (segment === undefined) {
          return (
            <span className="mailpoet_error">
              { MailPoet.I18n.t('sendingToSegmentsNotSpecified') }
            </span>
          );
        }
        sendingEvent = MailPoet.I18n.t('welcomeEventSegment').replace(
            '%$1s', segment.name
          );

        break;
    }

    // set sending delay
    if (sendingEvent) {
      if (newsletter.options.afterTimeType !== 'immediate') {
        switch (newsletter.options.afterTimeType) {
          case 'hours':
            sendingDelay = MailPoet.I18n.t('sendingDelayHours').replace(
              '%$1d', newsletter.options.afterTimeNumber
            );
            break;

          case 'days':
            sendingDelay = MailPoet.I18n.t('sendingDelayDays').replace(
              '%$1d', newsletter.options.afterTimeNumber
            );
            break;

          case 'weeks':
            sendingDelay = MailPoet.I18n.t('sendingDelayWeeks').replace(
              '%$1d', newsletter.options.afterTimeNumber
            );
            break;
        }
        sendingEvent += ' [' + sendingDelay + ']';
      }
      // add a "period" at the end if we do have a sendingEvent
      sendingEvent += '.';
    }

    return (
      <span>
        { sendingEvent }
      </span>
    );
  },
  renderItem: function (newsletter, actions) {
    const rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    return (
      <div>
        <td className={rowClasses}>
          <strong>
            <a
              className="row-title"
              href={`?page=mailpoet-newsletter-editor&id=${newsletter.id}`}
            >{ newsletter.subject }</a>
          </strong>
          { actions }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('status')}>
          { this.renderStatus(newsletter) }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('settings')}>
          { this.renderSettings(newsletter) }
        </td>
        { (mailpoet_tracking_enabled === true) ? (
          <td className="column" data-colname={MailPoet.I18n.t('statistics')}>
            { this.renderStatistics(
              newsletter,
              newsletter.total_sent > 0 && newsletter.statistics
            ) }
          </td>
        ) : null }
        <td className="column-date" data-colname={MailPoet.I18n.t('lastModifiedOn')}>
          <abbr>{ MailPoet.Date.format(newsletter.updated_at) }</abbr>
        </td>
      </div>
    );
  },
  render: function () {
    return (
      <div>
        <h1 className="title">
          { MailPoet.I18n.t('pageTitle') } <Link className="page-title-action" to="/new" data-automation-id="new_email">{ MailPoet.I18n.t('new') }</Link>
        </h1>

        <ListingTabs tab="welcome" />

        <Listing
          limit={window.mailpoet_listing_per_page}
          location={this.props.location}
          params={this.props.params}
          endpoint="newsletters"
          type="welcome"
          base_url="welcome"
          onRenderItem={this.renderItem}
          columns={columns}
          bulk_actions={bulk_actions}
          item_actions={newsletter_actions}
          messages={messages}
          auto_refresh={true}
          sort_by="updated_at"
          sort_order="desc"
          afterGetItems={this.checkMailerStatus}
        />
      </div>
    );
  },
});

module.exports = NewsletterListWelcome;
