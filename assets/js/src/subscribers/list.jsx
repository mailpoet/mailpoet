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
    label: 'Subscriber',
    sortable: true
  },
  {
    name: 'status',
    label: 'Status',
    sortable: true
  },
  {
    name: 'segments',
    label: 'Lists',
    sortable: false
  },

  {
    name: 'created_at',
    label: 'Subscribed on',
    sortable: true
  },
  {
    name: 'updated_at',
    label: 'Last modified on',
    sortable: true
  },
];

const messages = {
  onTrash: function(response) {
    if(response) {
      var message = null;
      if(~~response === 1) {
        message = (
          '1 subscriber was moved to the trash.'
        );
      } else if(~~response > 1) {
        message = (
          '%$1d subscribers were moved to the trash.'
        ).replace('%$1d', ~~response);
      }

      if(message !== null) {
        MailPoet.Notice.success(message);
      }
    }
  },
  onDelete: function(response) {
    if(response) {
      var message = null;
      if(~~response === 1) {
        message = (
          '1 subscriber was permanently deleted.'
        );
      } else if(~~response > 1) {
        message = (
          '%$1d subscribers were permanently deleted.'
        ).replace('%$1d', ~~response);
      }

      if(message !== null) {
        MailPoet.Notice.success(message);
      }
    }
  },
  onRestore: function(response) {
    if(response) {
      var message = null;
      if(~~response === 1) {
        message = (
          '1 subscriber has been restored from the trash.'
        );
      } else if(~~response > 1) {
        message = (
          '%$1d subscribers have been restored from the trash.'
        ).replace('%$1d', ~~response);
      }

      if(message !== null) {
        MailPoet.Notice.success(message);
      }
    }
  }
};

const bulk_actions = [
  {
    name: 'moveToList',
    label: 'Move to list...',
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
        '%$1d subscribers were moved to list <strong>%$2s</strong>.'
        .replace('%$1d', ~~response.subscribers)
        .replace('%$2s', response.segment)
      );
    }
  },
  {
    name: 'addToList',
    label: 'Add to list...',
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
        '%$1d subscribers were added to list <strong>%$2s</strong>.'
        .replace('%$1d', ~~response.subscribers)
        .replace('%$2s', response.segment)
      );
    }
  },
  {
    name: 'removeFromList',
    label: 'Remove from list...',
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
        '%$1d subscribers were removed from list <strong>%$2s</strong>.'
        .replace('%$1d', ~~response.subscribers)
        .replace('%$2s', response.segment)
      );
    }
  },
  {
    name: 'removeFromAllLists',
    label: 'Remove from all lists',
    onSuccess: function(response) {
      MailPoet.Notice.success(
        '%$1d subscribers were removed from all lists.'
        .replace('%$1d', ~~response)
      );
    }
  },
  {
    name: 'confirmUnconfirmed',
    label: 'Confirm unconfirmed',
    onSuccess: function(response) {
      MailPoet.Notice.success(
        '%$1d subscribers have been confirmed.'
        .replace('%$1d', ~~response)
      );
    }
  },
  {
    name: 'sendConfirmationEmail',
    label: 'Resend confirmation email',
    onSuccess: function(response) {
      MailPoet.Notice.success(
        '%$1d confirmation emails have been sent.'
        .replace('%$1d', ~~response)
      );
    }
  },
  {
    name: 'trash',
    label: 'Trash',
    onSuccess: messages.onTrash
  }
];

const item_actions = [
  {
    name: 'edit',
    label: 'Edit',
    link: function(item) {
      return (
        <Link to={ `/edit/${item.id}` }>Edit</Link>
      );
    }
  },
  {
    name: 'trash'
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
        status = 'Subscribed';
      break;

      case 'unconfirmed':
        status = 'Unconfirmed';
      break;

      case 'unsubscribed':
        status = 'Unsubscribed';
      break;
    }

    let segments = false;

    if (subscriber.subscriptions.length > 0) {
      let subscribed_segments = [];
      let unsubscribed_segments = [];

      subscriber.subscriptions.map((subscription) => {
        const segment = this.getSegmentFromId(subscription.segment_id);
        if(segment === false) return;
        if (subscription.status === 'subscribed') {
          subscribed_segments.push(segment.name);
        } else if (subscription.status === 'unsubscribed') {
          unsubscribed_segments.push(segment.name);
        }
      });
      segments = (
        <span>
          <span className="mailpoet_segments_subscribed">
            { subscribed_segments.join(', ') }
            {
              (
                subscribed_segments.length > 0
                && unsubscribed_segments.length > 0
              ) ? ' / ' : ''
            }
          </span>
          <span
            className="mailpoet_segments_unsubscribed"
            title="Lists to which the subscriber was subscribed."
          >
            { unsubscribed_segments.join(', ') }
          </span>
        </span>
      );
    }

    let avatar = false;
    if(subscriber.avatar_url) {
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
          <strong><Link to={ `/edit/${ subscriber.id }` }>
            { subscriber.email }
          </Link></strong>
          <p style={{margin: 0}}>
            { subscriber.first_name } { subscriber.last_name }
          </p>
          { actions }
        </td>
        <td className="column" data-colname="Status">
          { status }
        </td>
        <td className="column" data-colname="Lists">
          { segments }
        </td>
        <td className="column-date" data-colname="Subscribed on">
          <abbr>{ MailPoet.Date.full(subscriber.created_at) }</abbr>
        </td>
        <td className="column-date" data-colname="Last modified on">
          <abbr>{ MailPoet.Date.full(subscriber.updated_at) }</abbr>
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
        <h2 className="title">
          Subscribers <Link className="add-new-h2" to="/new">New</Link>
          <a className="add-new-h2" href="?page=mailpoet-import#step1">Import</a>
          <a id="mailpoet_export_button" className="add-new-h2" href="?page=mailpoet-export">Export</a>
        </h2>

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
        />
      </div>
    )
  }
});

module.exports = SubscriberList;