import React from 'react'
import { Router, Link } from 'react-router'

import jQuery from 'jquery'
import MailPoet from 'mailpoet'
import classNames from 'classnames'

import Listing from 'listing/listing.jsx'

var columns = [
  {
    name: 'name',
    label: 'Name',
    sortable: true
  },
  {
    name: 'description',
    label: 'Description',
    sortable: false
  },
  {
    name: 'subscribed',
    label: 'Subscribed',
    sortable: false
  },
  {
    name: 'unconfirmed',
    label: 'Unconfirmed',
    sortable: false
  },
  {
    name: 'unsubscribed',
    label: 'Unsubscribed',
    sortable: false
  },
  {
    name: 'created_at',
    label: 'Created on',
    sortable: true
  }
];

const messages = {
  onTrash: function(response) {
    if(response) {
      let message = null;
      if(~~response === 1) {
        message = (
          '1 segment was moved to the trash.'
        );
      } else if(~~response > 1) {
        message = (
          '%$1d segments were moved to the trash.'
        ).replace('%$1d', ~~response);
      }

      if(message !== null) {
        MailPoet.Notice.success(message);
      }
    }
  },
  onDelete: function(response) {
    if(response) {
      let message = null;
      if(~~response === 1) {
        message = (
          '1 segment was permanently deleted.'
        );
      } else if(~~response > 1) {
        message = (
          '%$1d segments were permanently deleted.'
        ).replace('%$1d', ~~response);
      }

      if(message !== null) {
        MailPoet.Notice.success(message);
      }
    }
  },
  onRestore: function(response) {
    if(response) {
      let message = null;
      if(~~response === 1) {
        message = (
          '1 segment has been restored from the trash.'
        );
      } else if(~~response > 1) {
        message = (
          '%$1d segments have been restored from the trash.'
        ).replace('%$1d', ~~response);
      }

      if(message !== null) {
        MailPoet.Notice.success(message);
      }
    }
  }
};

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
    name: 'duplicate_segment',
    label: 'Duplicate',
    onClick: function(item, refresh) {
      return MailPoet.Ajax.post({
        endpoint: 'segments',
        action: 'duplicate',
        data: item.id
      }).done(function(response) {
        MailPoet.Notice.success(
          ('List "%$1s" has been duplicated.').replace('%$1s', response.name)
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
    label: 'Update',
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
            ('List "%$1s" has been synchronized.').replace('%$1s', item.name)
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
        <a href={ item.subscribers_url }>View subscribers</a>
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

const bulk_actions = [
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
          <abbr>{ segment.subscribed || 0 }</abbr>
        </td>
        <td className="column-date" data-colname="Unconfirmed">
          <abbr>{ segment.unconfirmed || 0 }</abbr>
        </td>
        <td className="column-date" data-colname="Unsubscribed">
          <abbr>{ segment.unsubscribed || 0 }</abbr>
        </td>
        <td className="column-date" data-colname="Created on">
          <abbr>{ segment.created_at }</abbr>
        </td>
      </div>
    );
  },
  render: function() {
    return (
      <div>
        <h2 className="title">
          Segments <Link className="add-new-h2" to="/new">New</Link>
        </h2>

        <Listing
          location={ this.props.location }
          params={ this.props.params }
          messages={ messages }
          search={ false }
          limit={ 1000 }
          endpoint="segments"
          onRenderItem={ this.renderItem }
          columns={ columns }
          bulk_actions={ bulk_actions }
          item_actions={ item_actions }
        />
      </div>
    );
  }
});

module.exports = SegmentList;