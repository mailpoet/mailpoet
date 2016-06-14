import React from 'react'
import { Router, Route, IndexRoute, Link, useRouterHistory } from 'react-router'
import { createHashHistory } from 'history'

import Listing from 'listing/listing.jsx'
import ListingTabs from 'newsletters/listings/tabs.jsx'

import classNames from 'classnames'
import jQuery from 'jquery'
import MailPoet from 'mailpoet'

const messages = {
  onTrash(response) {
    const count = ~~response;
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersTrashed')
      ).replace('%$1d', count);
    }
    MailPoet.Notice.success(message);
  },
  onDelete(response) {
    const count = ~~response;
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersDeleted')
      ).replace('%$1d', count);
    }
    MailPoet.Notice.success(message);
  },
  onRestore(response) {
    const count = ~~response;
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersRestored')
      ).replace('%$1d', count);
    }
    MailPoet.Notice.success(message);
  }
};

var columns = [
  {
    name: 'subject',
    label: MailPoet.I18n.t('subject'),
    sortable: true
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status')
  },
  {
    name: 'settings',
    label: MailPoet.I18n.t('settings')
  },
  {
    name: 'history',
    label: MailPoet.I18n.t('history')
  },
  {
    name: 'updated_at',
    label: MailPoet.I18n.t('lastModifiedOn'),
    sortable: true
  }
];

var bulk_actions = [
  {
    name: 'trash',
    label: MailPoet.I18n.t('trash'),
    onSuccess: messages.onTrash
  }
];

var newsletter_actions = [
  {
    name: 'view',
    link: function(newsletter) {
      return (
        <a href={ newsletter.preview_url } target="_blank">
          {MailPoet.I18n.t('preview')}
        </a>
      );
    }
  },
  {
    name: 'edit',
    link: function(newsletter) {
      return (
        <a href={ `?page=mailpoet-newsletter-editor&id=${ newsletter.id }` }>
          {MailPoet.I18n.t('edit')}
        </a>
      );
    }
  },
  {
    name: 'duplicate',
    label: MailPoet.I18n.t('duplicate'),
    onClick: function(newsletter, refresh) {
      return MailPoet.Ajax.post({
        endpoint: 'newsletters',
        action: 'duplicate',
        data: newsletter.id
      }).done(function(response) {
        if (response !== false && response.subject !== undefined) {
          MailPoet.Notice.success(
            (MailPoet.I18n.t('newsletterDuplicated')).replace(
              '%$1s', response.subject
            )
          );
        }
        refresh();
      });
    }
  },
  {
    name: 'trash'
  }
];

const NewsletterListNotification = React.createClass({
  updateStatus: function(e) {
    // make the event persist so that we can still override the selected value
    // in the ajax callback
    e.persist();

    MailPoet.Ajax.post({
      endpoint: 'newsletters',
      action: 'setStatus',
      data: {
        id: ~~(e.target.getAttribute('data-id')),
        status: e.target.value
      }
    }).done(function(response) {
      if (response.result === false) {
        MailPoet.Notice.error(MailPoet.I18n.t('postNotificationActivationFailed'));

        // reset value to actual newsletter's status
         e.target.value = response.status;
      } else {
        if (response.status === 'active') {
          MailPoet.Notice.success(MailPoet.I18n.t('postNotificationActivated'));
        }
        // force refresh of listing so that groups are updated
        this.forceUpdate();
      }
    }.bind(this));
  },
  renderStatus: function(newsletter) {
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
  renderSettings: function(newsletter) {
    return (
      <span>
        Settings...
      </span>
    );
  },
  renderItem: function(newsletter, actions) {
    var rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    return (
      <div>
        <td className={ rowClasses }>
          <strong>
            <a href={ `?page=mailpoet-newsletter-editor&id=${ newsletter.id }` }>
              { newsletter.subject }
            </a>
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
          <a href="#TODO">{ MailPoet.I18n.t('viewHistory') }</a>
        </td>
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

        <Listing
          limit={ mailpoet_listing_per_page }
          params={ this.props.params }
          endpoint="newsletters"
          tab="notification"
          onRenderItem={ this.renderItem }
          columns={ columns }
          bulk_actions={ bulk_actions }
          item_actions={ newsletter_actions }
          messages={ messages }
          auto_refresh={ true }
        />
      </div>
    );
  }
});

module.exports = NewsletterListNotification;