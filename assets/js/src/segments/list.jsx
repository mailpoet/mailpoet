import React from 'react'
import { Router, Link } from 'react-router'

import jQuery from 'jquery'
import MailPoet from 'mailpoet'
import classNames from 'classnames'

import Listing from 'listing/listing.jsx'

var columns = [
  {
    name: 'name',
    label: MailPoet.I18n.t('name'),
    sortable: true
  },
  {
    name: 'description',
    label: MailPoet.I18n.t('description'),
    sortable: false
  },
  {
    name: 'subscribed',
    label: MailPoet.I18n.t('subscribed'),
    sortable: false
  },
  {
    name: 'unconfirmed',
    label: MailPoet.I18n.t('unconfirmed'),
    sortable: false
  },
  {
    name: 'unsubscribed',
    label: MailPoet.I18n.t('unsubscribed'),
    sortable: false
  },
  {
    name: 'created_at',
    label: MailPoet.I18n.t('createdOn'),
    sortable: true
  }
];

const messages = {
  onTrash: function(response) {
    var count = ~~response;
    var message = null;

    if(count === 1) {
      message = (
        MailPoet.I18n.t('oneSegmentTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleSegmentsTrashed')
      ).replace('%$1d', count);
    }
    MailPoet.Notice.success(message);
  },
  onDelete: function(response) {
    var count = ~~response;
    var message = null;

    if(count === 1) {
      message = (
        MailPoet.I18n.t('oneSegmentDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleSegmentsDeleted')
      ).replace('%$1d', count);
    }
    MailPoet.Notice.success(message);
  },
  onRestore: function(response) {
    var count = ~~response;
    var message = null;

    if(count === 1) {
      message = (
        MailPoet.I18n.t('oneSegmentRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleSegmentsRestored')
      ).replace('%$1d', count);
    }
    MailPoet.Notice.success(message);
  }
};

const item_actions = [
  {
    name: 'edit',
    label: MailPoet.I18n.t('edit'),
    link: function(item) {
      return (
        <Link to={ `/edit/${item.id}` }>{MailPoet.I18n.t('edit')}</Link>
      );
    },
    display: function(segment) {
      return (segment.type !== 'wp_users');
    }
  },
  {
    name: 'duplicate_segment',
    label: 'Duplicate',
    onClick: function(item, refresh) {
      return MailPoet.Ajax.post({
        endpoint: 'segments',
        action: 'duplicate',
        data: item.id
      }).done(function(response) {
        MailPoet.Notice.success(
          (MailPoet.I18n.t('listDuplicated')).replace('%$1s', response.name)
        );
        refresh();
      });
    },
    display: function(segment) {
      return (segment.type !== 'wp_users');
    }
  },
  {
    name: 'synchronize_segment',
    label: MailPoet.I18n.t('update'),
    className: 'update',
    onClick: function(item, refresh) {
      MailPoet.Modal.loading(true);
      MailPoet.Ajax.post({
        endpoint: 'segments',
        action: 'synchronize'
      }).done(function(response) {
        MailPoet.Modal.loading(false);
        if(response === true) {
          MailPoet.Notice.success(
            (MailPoet.I18n.t('listSynchronized')).replace('%$1s', item.name)
          );
          refresh();
        }
      });
    },
    display: function(segment) {
      return (segment.type === 'wp_users');
    }
  },
  {
    name: 'view_subscribers',
    link: function(item) {
      return (
        <a href={ item.subscribers_url }>{MailPoet.I18n.t('viewSubscribers')}</a>
      );
    }
  },
  {
    name: 'trash',
    display: function(segment) {
      return (segment.type !== 'wp_users');
    }
  }
];

const SegmentList = React.createClass({
  renderItem: function(segment, actions) {
    var rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );
    return (
      <div>
        <td className={ rowClasses }>
          <strong>
            <a>{ segment.name }</a>
          </strong>
          { actions }
        </td>
        <td className="column-date" data-colname="Description">
          <abbr>{ segment.description }</abbr>
        </td>
        <td className="column-date" data-colname="Subscribed">
          <abbr>{ segment.subscribers_count.subscribed || 0 }</abbr>
        </td>
        <td className="column-date" data-colname="Unconfirmed">
          <abbr>{ segment.subscribers_count.unconfirmed || 0 }</abbr>
        </td>
        <td className="column-date" data-colname="Unsubscribed">
          <abbr>{ segment.subscribers_count.unsubscribed || 0 }</abbr>
        </td>
        <td className="column-date" data-colname="Created on">
          <abbr>{ MailPoet.Date.full(segment.created_at) }</abbr>
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

        <Listing
          location={ this.props.location }
          params={ this.props.params }
          messages={ messages }
          search={ false }
          limit={ 1000 }
          endpoint="segments"
          onRenderItem={ this.renderItem }
          columns={ columns }
          item_actions={ item_actions }
        />
      </div>
    );
  }
});

module.exports = SegmentList;
