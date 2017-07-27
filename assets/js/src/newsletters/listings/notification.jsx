import React from 'react';
import { Link } from 'react-router';

import Listing from 'listing/listing.jsx';
import ListingTabs from 'newsletters/listings/tabs.jsx';

import { MailerMixin } from 'newsletters/listings/mixins.jsx';

import classNames from 'classnames';
import MailPoet from 'mailpoet';

import {
  timeOfDayValues,
  weekDayValues,
  monthDayValues,
  nthWeekDayValues,
} from 'newsletters/scheduling/common.jsx';

const messages = {
  onTrash: (response) => {
    const count = ~~response.meta.count;
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
    const count = ~~response.meta.count;
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
    const count = ~~response.meta.count;
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
    width: 100,
  },
  {
    name: 'settings',
    label: MailPoet.I18n.t('settings'),
  },
  {
    name: 'history',
    label: MailPoet.I18n.t('history'),
    width: 100,
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

const newsletter_actions = [
  {
    name: 'view',
    link: function (newsletter) {
      return (
        <a href={ newsletter.preview_url } target="_blank">
          {MailPoet.I18n.t('preview')}
        </a>
      );
    },
  },
  {
    name: 'edit',
    link: function (newsletter) {
      return (
        <a href={ `?page=mailpoet-newsletter-editor&id=${ newsletter.id }` }>
          {MailPoet.I18n.t('edit')}
        </a>
      );
    },
  },
  {
    name: 'duplicate',
    label: MailPoet.I18n.t('duplicate'),
    onClick: function (newsletter, refresh) {
      return MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'newsletters',
        action: 'duplicate',
        data: {
          id: newsletter.id,
        },
      }).done((response) => {
        MailPoet.Notice.success(
          (MailPoet.I18n.t('newsletterDuplicated')).replace(
            '%$1s', response.data.subject
          )
        );
        refresh();
      }).fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map((error) => { return error.message; }),
            { scroll: true }
          );
        }
      });
    },
  },
  {
    name: 'trash',
  },
];

const NewsletterListNotification = React.createClass({
  mixins: [ MailerMixin ],
  updateStatus: function (e) {
    // make the event persist so that we can still override the selected value
    // in the ajax callback
    e.persist();

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'setStatus',
      data: {
        id: ~~(e.target.getAttribute('data-id')),
        status: e.target.value,
      },
    }).done((response) => {
      if (response.data.status === 'active') {
        MailPoet.Notice.success(MailPoet.I18n.t('postNotificationActivated'));
      }
      // force refresh of listing so that groups are updated
      this.forceUpdate();
    }).fail((response) => {
      MailPoet.Notice.error(MailPoet.I18n.t('postNotificationActivationFailed'));

      // reset value to actual newsletter's status
      e.target.value = response.status;
    });
  },
  renderStatus: function (newsletter) {
    return (
      <select
        data-id={ newsletter.id }
        defaultValue={ newsletter.status }
        onChange={ this.updateStatus }
      >
        <option value="active">{ MailPoet.I18n.t('active') }</option>
        <option value="draft">{ MailPoet.I18n.t('inactive') }</option>
      </select>
    );
  },
  renderSettings: function (newsletter) {
    let sendingFrequency;
    let sendingToSegments;

    // get list of segments' name
    const segments = newsletter.segments.map((segment) => {
      return segment.name;
    });

    // check if the user has specified segments to send to
    if(segments.length === 0) {
      return (
        <span className="mailpoet_error">
          { MailPoet.I18n.t('sendingToSegmentsNotSpecified') }
        </span>
      );
    } else {
      sendingToSegments = MailPoet.I18n.t('ifNewContentToSegments').replace(
        '%$1s', segments.join(', ')
      );

      // set sending frequency
      switch (newsletter.options.intervalType) {
        case 'daily':
          sendingFrequency = MailPoet.I18n.t('sendDaily').replace(
            '%$1s', timeOfDayValues[newsletter.options.timeOfDay]
          );
          break;

        case 'weekly':
          sendingFrequency = MailPoet.I18n.t('sendWeekly').replace(
            '%$1s', weekDayValues[newsletter.options.weekDay]
          ).replace(
            '%$2s', timeOfDayValues[newsletter.options.timeOfDay]
          );
          break;

        case 'monthly':
          sendingFrequency = MailPoet.I18n.t('sendMonthly').replace(
            '%$1s', monthDayValues[newsletter.options.monthDay]
          ).replace(
            '%$2s', timeOfDayValues[newsletter.options.timeOfDay]
          );
          break;

        case 'nthWeekDay':
          sendingFrequency = MailPoet.I18n.t('sendNthWeekDay').replace(
            '%$1s', nthWeekDayValues[newsletter.options.nthWeekDay]
          ).replace(
            '%$2s', weekDayValues[newsletter.options.weekDay]
          ).replace(
            '%$3s', timeOfDayValues[newsletter.options.timeOfDay]
          );
          break;

        case 'immediately':
          sendingFrequency = MailPoet.I18n.t('sendImmediately');
          break;
      }
    }

    return (
      <span>
        { sendingFrequency } { sendingToSegments }
      </span>
    );
  },
  renderHistoryLink: function (newsletter) {
    const childrenCount = ~~(newsletter.children_count);
    if (childrenCount === 0) {
      return (
        MailPoet.I18n.t('notSentYet')
      );
    } else {
      return (
        <Link
          to={ `/notification/history/${ newsletter.id }` }
        >{ MailPoet.I18n.t('viewHistory') }</Link>
      );
    }
  },
  renderItem: function (newsletter, actions) {
    const rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    return (
      <div>
        <td className={ rowClasses }>
          <strong>
            <a
              className="row-title"
              href={ `?page=mailpoet-newsletter-editor&id=${ newsletter.id }` }
            >{ newsletter.subject }</a>
          </strong>
          { actions }
        </td>
        <td className="column" data-colname={ MailPoet.I18n.t('status') }>
          { this.renderStatus(newsletter) }
        </td>
        <td className="column" data-colname={ MailPoet.I18n.t('settings') }>
          { this.renderSettings(newsletter) }
        </td>
        <td className="column" data-colname={ MailPoet.I18n.t('history') }>
          { this.renderHistoryLink(newsletter) }
        </td>
        <td className="column-date" data-colname={ MailPoet.I18n.t('lastModifiedOn') }>
          <abbr>{ MailPoet.Date.format(newsletter.updated_at) }</abbr>
        </td>
      </div>
    );
  },
  render: function () {
    return (
      <div>
        <h1 className="title">
          {MailPoet.I18n.t('pageTitle')} <Link className="page-title-action" to="/new">{MailPoet.I18n.t('new')}</Link>
        </h1>

        <ListingTabs tab="notification" />

        <Listing
          limit={ mailpoet_listing_per_page }
          location={ this.props.location }
          params={ this.props.params }
          endpoint="newsletters"
          type="notification"
          base_url="notification"
          onRenderItem={ this.renderItem }
          columns={ columns }
          bulk_actions={ bulk_actions }
          item_actions={ newsletter_actions }
          messages={ messages }
          auto_refresh={ true }
          sort_by="updated_at"
          sort_order="desc"
          afterGetItems={ this.checkMailerStatus }
        />
      </div>
    );
  },
});

module.exports = NewsletterListNotification;
