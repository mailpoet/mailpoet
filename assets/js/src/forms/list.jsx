import React from 'react'
import ReactDOM from 'react-dom'
import { Router, Link } from 'react-router'
import Listing from 'listing/listing.jsx'
import classNames from 'classnames'
import MailPoet from 'mailpoet'

const columns = [
  {
    name: 'name',
    label: MailPoet.I18n.t('formName'),
    sortable: true
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('segments'),
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
        MailPoet.I18n.t('oneFormTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleFormsTrashed')
      ).replace('%$1d', count);
    }
    MailPoet.Notice.success(message);
  },
  onDelete: function(response) {
    var count = ~~response;
    var message = null;

    if(count === 1) {
      message = (
        MailPoet.I18n.t('oneFormDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleFormsDeleted')
      ).replace('%$1d', count);
    }
    MailPoet.Notice.success(message);
  },
  onRestore: function(response) {
    var count = ~~response;
    var message = null;

    if(count === 1) {
      message = (
        MailPoet.I18n.t('oneFormRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleFormsRestored')
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
        <a href={ `admin.php?page=mailpoet-form-editor&id=${item.id}` }>{MailPoet.I18n.t('edit')}</a>
      );
    }
  },
  {
    name: 'duplicate_form',
    label: MailPoet.I18n.t('duplicate'),
    onClick: function(item, refresh) {
      return MailPoet.Ajax.post({
        endpoint: 'forms',
        action: 'duplicate',
        data: item.id
      }).done(function(response) {
        MailPoet.Notice.success(
          (MailPoet.I18n.t('formDuplicated')).replace('%$1s', response.name)
        );
        refresh();
      });
    }
  },
  {
    name: 'trash'
  }
];

const FormList = React.createClass({
  createForm() {
    MailPoet.Ajax.post({
      endpoint: 'forms',
      action: 'create'
    }).done(function(response) {
      if(response.result && response.form_id) {
        window.location = mailpoet_form_edit_url + response.form_id;
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
            <a
              className="row-title"
              href={ `admin.php?page=mailpoet-form-editor&id=${form.id}` }
            >{ form.name }</a>
          </strong>
          { actions }
        </td>
        <td className="column-format" data-colname={MailPoet.I18n.t('segments')}>
          { segments }
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('createdOn')}>
          <abbr>{ MailPoet.Date.format(form.created_at) }</abbr>
        </td>
      </div>
    );
  },
  render() {
    return (
      <div>
        <h1 className="title">
          {MailPoet.I18n.t('pageTitle')} <a
            className="page-title-action"
            href="javascript:;"
            onClick={ this.createForm }
          >{MailPoet.I18n.t('new')}</a>
        </h1>

        <Listing
          location={ this.props.location }
          params={ this.props.params }
          messages={ messages }
          search={ false }
          limit={ 1000 }
          endpoint="forms"
          onRenderItem={ this.renderItem }
          columns={ columns }
          item_actions={ item_actions }
        />
      </div>
    );
  }
});

module.exports = FormList;
