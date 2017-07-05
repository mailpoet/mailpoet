import React from 'react';
import Listing from 'listing/listing.jsx';
import classNames from 'classnames';
import MailPoet from 'mailpoet';

const columns = [
  {
    name: 'name',
    label: MailPoet.I18n.t('formName'),
    sortable: true
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('segments')
  },
  {
    name: 'signups',
    label: MailPoet.I18n.t('signups')
  },
  {
    name: 'created_at',
    label: MailPoet.I18n.t('createdOn'),
    sortable: true
  }
];

const messages = {
  onTrash: (response) => {
    const count = ~~response.meta.count;
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneFormTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleFormsTrashed')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = ~~response.meta.count;
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneFormDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleFormsDeleted')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = ~~response.meta.count;
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneFormRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleFormsRestored')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  }
};

const bulk_actions = [
  {
    name: 'trash',
    label: MailPoet.I18n.t('moveToTrash'),
    onSuccess: messages.onTrash
  }
];

const item_actions = [
  {
    name: 'edit',
    label: MailPoet.I18n.t('edit'),
    link: function (item) {
      return (
        <a href={ `admin.php?page=mailpoet-form-editor&id=${item.id}` }>{MailPoet.I18n.t('edit')}</a>
      );
    }
  },
  {
    name: 'duplicate',
    label: MailPoet.I18n.t('duplicate'),
    onClick: function (item, refresh) {
      return MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'forms',
        action: 'duplicate',
        data: {
          id: item.id
        }
      }).done((response) => {
        MailPoet.Notice.success(
          (MailPoet.I18n.t('formDuplicated')).replace('%$1s', response.data.name)
        );
        refresh();
      }).fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(function (error) { return error.message; }),
            { scroll: true }
          );
        }
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
      api_version: window.mailpoet_api_version,
      endpoint: 'forms',
      action: 'create'
    }).done((response) => {
      window.location = mailpoet_form_edit_url + response.data.id;
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(function (error) { return error.message; }),
          { scroll: true }
        );
      }
    });
  },
  renderItem(form, actions) {
    let row_classes = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    let segments = mailpoet_segments.filter(function (segment) {
      return (jQuery.inArray(segment.id, form.segments) !== -1);
    }).map(function (segment) {
      return segment.name;
    }).join(', ');

    if (form.settings.segments_selected_by === 'user') {
      segments = MailPoet.I18n.t('userChoice') + ' ' + segments;
    }

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
        <td className="column" data-colname={MailPoet.I18n.t('segments')}>
          { segments }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('signups')}>
          { form.signups }
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
          limit={ mailpoet_listing_per_page }
          location={ this.props.location }
          params={ this.props.params }
          messages={ messages }
          search={ false }
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
