import React from 'react';
import { Link } from 'react-router';
import { confirmAlert } from 'react-confirm-alert';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import Hooks from 'wp-js-hooks';

import Listing from 'listing/listing.jsx';
import ListingTabs from 'newsletters/listings/tabs.jsx';

import {
  QueueMixin,
  StatisticsMixin,
  MailerMixin,
} from 'newsletters/listings/mixins.jsx';

const mailpoetTrackingEnabled = (!!(window.mailpoet_tracking_enabled));

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
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('lists'),
  },
  {
    name: 'statistics',
    label: MailPoet.I18n.t('statistics'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'sent_at',
    label: MailPoet.I18n.t('sentOn'),
    sortable: true,
  },
];

const bulkActions = [
  {
    name: 'trash',
    label: MailPoet.I18n.t('moveToTrash'),
    onSuccess: messages.onTrash,
  },
];

const confirmEdit = (newsletter) => {
  const redirectToEditing = () => {
    window.location.href = `?page=mailpoet-newsletter-editor&id=${newsletter.id}`;
  };
  if (
    !newsletter.queue
    || newsletter.status !== 'sending'
    || newsletter.queue.status !== null
  ) {
    redirectToEditing();
  } else {
    confirmAlert({
      title: MailPoet.I18n.t('confirmTitle'),
      message: MailPoet.I18n.t('confirmEdit'),
      confirmLabel: MailPoet.I18n.t('confirmLabel'),
      cancelLabel: MailPoet.I18n.t('cancelLabel'),
      onConfirm: redirectToEditing,
    });
  }
};

let newsletterActions = [
  {
    name: 'view',
    link: function link(newsletter) {
      return (
        <a href={newsletter.preview_url} target="_blank">
          {MailPoet.I18n.t('preview')}
        </a>
      );
    },
  },
  {
    name: 'edit',
    label: MailPoet.I18n.t('edit'),
    onClick: confirmEdit,
  },
  {
    name: 'duplicate',
    label: MailPoet.I18n.t('duplicate'),
    onClick: function onClick(newsletter, refresh) {
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
            response.errors.map(error => error.message),
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

Hooks.addFilter('mailpoet_newsletters_listings_standard_actions', StatisticsMixin.addStatsCTAAction);
newsletterActions = Hooks.applyFilters('mailpoet_newsletters_listings_standard_actions', newsletterActions);

const NewsletterListStandard = React.createClass({
  mixins: [QueueMixin, StatisticsMixin, MailerMixin],
  renderItem: function renderItem(newsletter, actions, meta) {
    const rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    const segments = newsletter.segments.map(segment => segment.name).join(', ');

    return (
      <div>
        <td className={rowClasses}>
          <strong>
            <a
              className="row-title"
              href="javascript:;"
              onClick={() => confirmEdit(newsletter)}
            >{ newsletter.queue.newsletter_rendered_subject || newsletter.subject }</a>
          </strong>
          { actions }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('status')}>
          { this.renderQueueStatus(newsletter, meta.mta_log) }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('lists')}>
          { segments }
        </td>
        { (mailpoetTrackingEnabled === true) ? (
          <td className="column" data-colname={MailPoet.I18n.t('statistics')}>
            { this.renderStatistics(newsletter, undefined, meta.current_time) }
          </td>
        ) : null }
        <td className="column-date" data-colname={MailPoet.I18n.t('sentOn')}>
          <abbr>{ (newsletter.sent_at) ? MailPoet.Date.format(newsletter.sent_at) : MailPoet.I18n.t('notSentYet') }</abbr>
        </td>
      </div>
    );
  },
  render: function render() {
    return (
      <div>
        <h1 className="title">
          {MailPoet.I18n.t('pageTitle')}
          <Link className="page-title-action" to="/new"
            onClick={() => MailPoet.trackEvent('Emails > Add New',
              { 'MailPoet Free version': window.mailpoet_version }
            )}
            data-automation-id="new_email"
          >
            {MailPoet.I18n.t('new')}
          </Link>
        </h1>

        <ListingTabs tab="standard" />

        <Listing
          limit={window.mailpoet_listing_per_page}
          location={this.props.location}
          params={this.props.params}
          endpoint="newsletters"
          type="standard"
          base_url="standard"
          onRenderItem={this.renderItem}
          columns={columns}
          bulk_actions={bulkActions}
          item_actions={newsletterActions}
          messages={messages}
          auto_refresh={true}
          sort_by="sent_at"
          sort_order="desc"
          afterGetItems={this.checkMailerStatus}
        />
      </div>
    );
  },
});

module.exports = NewsletterListStandard;
