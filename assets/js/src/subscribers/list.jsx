import React from 'react'
import { Router, Route, Link } from 'react-router'

import jQuery from 'jquery'
import MailPoet from 'mailpoet'
import classNames from 'classnames'

import Listing from 'listing/listing.jsx'
import Selection from 'form/fields/selection.jsx'

const columns = [
  {
    name: 'email',
    label: MailPoet.I18n.t('subscriber'),
    sortable: true
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
    sortable: true
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('lists')
  },

  {
    name: 'created_at',
    label: MailPoet.I18n.t('subscribedOn'),
    sortable: true
  },
  {
    name: 'updated_at',
    label: MailPoet.I18n.t('lastModifiedOn'),
    sortable: true
  },
];

const messages = {
  onTrash: function(response) {
    if (response) {
      var message = null;
      if (~~response === 1) {
        message = (
          MailPoet.I18n.t('oneSubscriberTrashed')
        );
      } else if (~~response > 1) {
        message = (
          MailPoet.I18n.t('multipleSubscribersTrashed')
        ).replace('%$1d', (~~response).toLocaleString());
      }

      if (message !== null) {
        MailPoet.Notice.success(message);
      }
    }
  },
  onDelete: function(response) {
    if (response) {
      var message = null;
      if (~~response === 1) {
        message = (
          MailPoet.I18n.t('oneSubscriberDeleted')
        );
      } else if (~~response > 1) {
        message = (
          MailPoet.I18n.t('multipleSubscribersDeleted')
        ).replace('%$1d', ~~response);
      }

      if (message !== null) {
        MailPoet.Notice.success(message);
      }
    }
  },
  onRestore: function(response) {
    if (response) {
      var message = null;
      if (~~response === 1) {
        message = (
          MailPoet.I18n.t('oneSubscriberRestored')
        );
      } else if (~~response > 1) {
        message = (
          MailPoet.I18n.t('multipleSubscribersRestored')
        ).replace('%$1d', (~~response).toLocaleString());
      }

      if (message !== null) {
        MailPoet.Notice.success(message);
      }
    }
  }
};

const bulk_actions = [
  {
    name: 'moveToList',
    label: MailPoet.I18n.t('moveToList'),
    onSelect: function() {
      let field = {
        id: 'move_to_segment',
        endpoint: 'segments',
        filter: function(segment) {
          return !!(
            !segment.deleted_at && segment.type === 'default'
          );
        }
      };

      return (
        <Selection field={ field }/>
      );
    },
    getData: function() {
      return {
        segment_id: ~~(jQuery('#move_to_segment').val())
      }
    },
    onSuccess: function(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersMovedToList')
        .replace('%$1d', (~~(response.subscribers)).toLocaleString())
        .replace('%$2s', response.segment)
      );
    }
  },
  {
    name: 'addToList',
    label: MailPoet.I18n.t('addToList'),
    onSelect: function() {
      let field = {
        id: 'add_to_segment',
        endpoint: 'segments',
        filter: function(segment) {
          return !!(
            !segment.deleted_at && segment.type === 'default'
          );
        }
      };

      return (
        <Selection field={ field }/>
      );
    },
    getData: function() {
      return {
        segment_id: ~~(jQuery('#add_to_segment').val())
      }
    },
    onSuccess: function(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersAddedToList')
        .replace('%$1d', (~~response.subscribers).toLocaleString())
        .replace('%$2s', response.segment)
      );
    }
  },
  {
    name: 'removeFromList',
    label: MailPoet.I18n.t('removeFromList'),
    onSelect: function() {
      let field = {
        id: 'remove_from_segment',
        endpoint: 'segments',
        filter: function(segment) {
          return !!(
            segment.type === 'default'
          );
        }
      };

      return (
        <Selection field={ field }/>
      );
    },
    getData: function() {
      return {
        segment_id: ~~(jQuery('#remove_from_segment').val())
      }
    },
    onSuccess: function(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersRemovedFromList')
        .replace('%$1d', (~~response.subscribers).toLocaleString())
        .replace('%$2s', response.segment)
      );
    }
  },
  {
    name: 'removeFromAllLists',
    label: MailPoet.I18n.t('removeFromAllLists'),
    onSuccess: function(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleSubscribersRemovedFromAllLists')
        .replace('%$1d', (~~response).toLocaleString())
      );
    }
  },
  {
    name: 'sendConfirmationEmail',
    label: MailPoet.I18n.t('resendConfirmationEmail'),
    onSuccess: function(response) {
      MailPoet.Notice.success(
        MailPoet.I18n.t('multipleConfirmationEmailsSent')
        .replace('%$1d', (~~response).toLocaleString())
      );
    }
  },
  {
    name: 'trash',
    label: MailPoet.I18n.t('trash'),
    onSuccess: messages.onTrash
  }
];

const item_actions = [
  {
    name: 'edit',
    label: MailPoet.I18n.t('edit'),
    link: function(subscriber) {
      return (
        <Link to={ `/edit/${subscriber.id}` }>{MailPoet.I18n.t('edit')}</Link>
      );
    }
  },
  {
    name: 'trash',
    display: function(subscriber) {
      return !!(~~subscriber.wp_user_id === 0);
    }
  }
];

const SubscriberList = React.createClass({
  getSegmentFromId: function(segment_id) {
    let result = false;
    mailpoet_segments.map(function(segment) {
      if (segment.id === segment_id) {
        result = segment;
      }
    });
    return result;
  },
  renderItem: function(subscriber, actions) {
    let row_classes = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions',
      'column-username'
    );

    let status = '';

    switch(subscriber.status) {
      case 'subscribed':
        status = MailPoet.I18n.t('subscribed');
      break;

      case 'unconfirmed':
        status = MailPoet.I18n.t('unconfirmed');
      break;

      case 'unsubscribed':
        status = MailPoet.I18n.t('unsubscribed');
      break;
    }

    let segments = false;

    // Subscriptions
    if (subscriber.subscriptions.length > 0) {
      let subscribed_segments = [];

      subscriber.subscriptions.map((subscription) => {
        const segment = this.getSegmentFromId(subscription.segment_id);
        if (segment === false) return;
        if (subscription.status === 'subscribed') {
          subscribed_segments.push(segment.name);
        }
      });

      segments = (
        <span>
          { subscribed_segments.join(', ') }
        </span>
      );
    }


    let avatar = false;
    if (subscriber.avatar_url) {
      avatar = (
        <img
          className="avatar"
          src={ subscriber.avatar_url }
          title=""
          width="32"
          height="32"
        />
      );
    }

    return (
      <div>
        <td className={ row_classes }>
          <strong>
            <Link
              className="row-title"
              to={ `/edit/${ subscriber.id }` }
            >{ subscriber.email }</Link>
          </strong>
          <p style={{margin: 0}}>
            { subscriber.first_name } { subscriber.last_name }
          </p>
          { actions }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('status')}>
          { status }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('lists')}>
          { segments }
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('subscribedOn')}>
          <abbr>{ MailPoet.Date.format(subscriber.created_at) }</abbr>
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('lastModifiedOn')}>
          <abbr>{ MailPoet.Date.format(subscriber.updated_at) }</abbr>
        </td>
      </div>
    );
  },
  onGetItems: function(count) {
    jQuery('#mailpoet_export_button')[(count > 0) ? 'show' : 'hide']();
  },
  render: function() {
    return (
      <div>
        <h1 className="title">
          {MailPoet.I18n.t('pageTitle')} <Link className="page-title-action" to="/new">{MailPoet.I18n.t('new')}</Link>
          <a className="page-title-action" href="?page=mailpoet-import#step1">{MailPoet.I18n.t('import')}</a>
          <a id="mailpoet_export_button" className="page-title-action" href="?page=mailpoet-export">{MailPoet.I18n.t('export')}</a>
        </h1>

        <Listing
          limit={ mailpoet_listing_per_page }
          location={ this.props.location }
          params={ this.props.params }
          endpoint="subscribers"
          onRenderItem={ this.renderItem }
          columns={ columns }
          bulk_actions={ bulk_actions }
          item_actions={ item_actions }
          messages={ messages }
          onGetItems={ this.onGetItems }
          sort_by={ 'created_at' }
          sort_order={ 'desc' }
        />
      </div>
    )
  }
});

module.exports = SubscriberList;
