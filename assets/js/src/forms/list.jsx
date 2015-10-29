import React from 'react'
import ReactDOM from 'react-dom'
import { Router, Link, History } from 'react-router'
import Listing from 'listing/listing.jsx'
import classNames from 'classnames'
import MailPoet from 'mailpoet'

const columns = [
  {
    name: 'name',
    label: 'Name',
    sortable: true
  },
  {
    name: 'created_at',
    label: 'Created on',
    sortable: true
  }
];

const messages = {
  onTrash: function(response) {
    let count = ~~response.forms;
    let message = null;

    if(count === 1 || response === true) {
      message = (
        '1 form was moved to the trash.'
      );
    } else if(count > 1) {
      message = (
        '%$1d forms were moved to the trash.'
      ).replace('%$1d', count);
    }

    if(message !== null) {
      MailPoet.Notice.success(message);
    }
  },
  onDelete: function(response) {
    let count = ~~response.forms;
    let message = null;

    if(count === 1 || response === true) {
      message = (
        '1 form was permanently deleted.'
      );
    } else if(count > 1) {
      message = (
        '%$1d forms were permanently deleted.'
      ).replace('%$1d', count);
    }

    if(message !== null) {
      MailPoet.Notice.success(message);
    }
  },
  onRestore: function(response) {
    let count = ~~response.forms;
    let message = null;

    if(count === 1 || response === true) {
      message = (
        '1 form has been restored from the trash.'
      );
    } else if(count > 1) {
      message = (
        '%$1d forms have been restored from the trash.'
      ).replace('%$1d', count);
    }

    if(message !== null) {
      MailPoet.Notice.success(message);
    }
  }
};

const item_actions = [
  {
    name: 'edit',
    link: function(item) {
      return (
        <Link to={ `/edit/${item.id}` }>Edit</Link>
      );
    }
  },
  {
    name: 'duplicate_form',
    refresh: true,
    link: function(item) {
      return (
        <a
          href="javascript:;"
          onClick={ this.onDuplicate.bind(null, item) }
        >Duplicate</a>
      );
    },
    onDuplicate: function(item) {
      MailPoet.Ajax.post({
        endpoint: 'forms',
        action: 'duplicate',
        data: item.id
      }).done(function() {
        MailPoet.Notice.success(
          ('List "%$1s" has been duplicated.').replace('%$1s', item.name)
        );
      });
    }
  }
];

const bulk_actions = [
  {
    name: 'trash',
    label: 'Trash',
    getData: function() {
      return {
        confirm: false
      }
    },
    onSuccess: messages.onDelete
  }
];

const FormList = React.createClass({
  renderItem: function(form, actions) {
    let row_classes = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    let segments = mailpoet_segments.filter(function(segment) {
      return (jQuery.inArray(segment.id, form.segments) !== -1);
    }).map(function(segment) {
      return segment.name;
    }).join(', ');

    return (
      <div>
        <td className={ row_classes }>
          <strong>
            <a>{ form.name }</a>
          </strong>
          { actions }
        </td>
        <td className="column-format" data-colname="Lists">
          { segments }
        </td>
        <td className="column-date" data-colname="Created on">
          <abbr>{ form.created_at }</abbr>
        </td>
      </div>
    );
  },
  render() {
    return (
      <div>
        <h2 className="title">
          Forms <Link className="add-new-h2" to="/new">New</Link>
        </h2>

        <Listing
          messages={ messages }
          search={ false }
          limit={ 1000 }
          endpoint="forms"
          onRenderItem={ this.renderItem }
          columns={ columns }
          bulk_actions={ bulk_actions }
          item_actions={ item_actions }
        />
      </div>
    );
  }
});

module.exports = FormList;