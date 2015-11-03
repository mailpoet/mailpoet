import React from 'react'
import ReactDOM from 'react-dom'
import { Router, Link } from 'react-router'
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
    name: 'segments',
    label: 'Lists',
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
          '1 form was moved to the trash.'
        );
      } else if(~~response > 1) {
        message = (
          '%$1d forms were moved to the trash.'
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
          '1 form was permanently deleted.'
        );
      } else if(~~response > 1) {
        message = (
          '%$1d forms were permanently deleted.'
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
          '1 form has been restored from the trash.'
        );
      } else if(~~response > 1) {
        message = (
          '%$1d forms have been restored from the trash.'
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
        <a href={ `admin.php?page=mailpoet-form-editor&id=${item.id}` }>Edit</a>
      );
    }
  },
  {
    name: 'duplicate_form',
    label: 'Duplicate',
    onClick: function(item, refresh) {
      return MailPoet.Ajax.post({
        endpoint: 'forms',
        action: 'duplicate',
        data: item.id
      }).done(function(response) {
        MailPoet.Notice.success(
          ('Form "%$1s" has been duplicated.').replace('%$1s', response.name)
        );
        refresh();
      });
    }
  }
];

const bulk_actions = [
  {
    name: 'trash',
    label: 'Trash',
    onSuccess: messages.onTrash
  }
];

const FormList = React.createClass({
  createForm() {
    MailPoet.Ajax.post({
      endpoint: 'forms',
      action: 'create'
    }).done(function(response) {
      if(response !== false) {
        window.location = response;
      }
    });
  },
  renderItem(form, actions) {
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
          Forms <a
            className="add-new-h2"
            href="javascript:;"
            onClick={ this.createForm }
          >New</a>
        </h2>

        <Listing
          location={ this.props.location }
          params={ this.props.params }
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