import React from 'react';
import _ from 'underscore';
import MailPoet from 'mailpoet';
import { confirmAlert } from 'react-confirm-alert';

/**
 * props = {
 *   index, id, newsletterId, name, description, thumbnail, readonly,
 *   beforeDelete, afterDelete, beforeSelect, afterSelect
 * }
 */
class TemplateBox extends React.Component {
  onDelete() {
    const { id, name, beforeDelete, afterDelete } = this.props;
    const onConfirm = () => {
      beforeDelete();
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'newsletterTemplates',
        action: 'delete',
        data: {
          id: id,
        },
      }).done(() => {
        afterDelete(true, id);
      }).fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(error => error.message),
            { scroll: true }
          );
        }
        afterDelete(false);
      });
    };
    confirmAlert({
      title: MailPoet.I18n.t('confirmTitle'),
      message: MailPoet.I18n.t('confirmTemplateDeletion').replace('%$1s', name),
      confirmLabel: MailPoet.I18n.t('confirmLabel'),
      cancelLabel: MailPoet.I18n.t('cancelLabel'),
      onConfirm: onConfirm,
      onCancel: () => {},
    });
  }

  onPreview() {
    MailPoet.Modal.popup({
      title: this.props.name,
      template: '<div class="mailpoet_boxes_preview" style="background-color: {{ body.globalStyles.body.backgroundColor }}"><img src="{{ thumbnail }}" /></div>',
      data: this.props,
    });
  }

  onSelect() {
    const { newsletterId, name, beforeSelect, afterSelect } = this.props;
    let body = this.props.body;

    if (!_.isUndefined(body)) {
      body = JSON.stringify(body);
    }

    beforeSelect();

    MailPoet.trackEvent('Emails > Template selected', {
      'MailPoet Free version': window.mailpoet_version,
      'Email name': name,
    });

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'save',
      data: {
        id: newsletterId,
        body: body,
      },
    }).done((response) => {
      afterSelect(true, response.data.id);
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
      afterSelect(false);
    });
  }

  render() {
    const { index, name, thumbnail, description, readonly } = this.props;
    const onDelete = this.onDelete.bind(this);
    const onPreview = this.onPreview.bind(this);
    const onSelect = this.onSelect.bind(this);

    const deleteLink = (
      <div className="mailpoet_delete">
        <a href="javascript:;" onClick={onDelete}>{MailPoet.I18n.t('delete')}</a>
      </div>
    );

    let preview = '';
    if (typeof thumbnail === 'string' && thumbnail.length > 0) {
      preview = (
        <a href="javascript:;" onClick={onPreview}>
          <img src={thumbnail} />
          <div className="mailpoet_overlay"></div>
        </a>
      );
    }

    return (
      <li>
        <div className="mailpoet_thumbnail">
          { preview }
        </div>

        <div className="mailpoet_description">
          <h3>{ name }</h3>
          <p>{ description }</p>
        </div>

        <div className="mailpoet_actions">
          <a
            className="button button-secondary"
            onClick={onPreview}
          >{MailPoet.I18n.t('preview')}</a>
            &nbsp;
          <a
            className="button button-primary"
            data-automation-id={`select_template_${index}`}
            onClick={onSelect}
            > {MailPoet.I18n.t('select')} </a>
        </div>
        { readonly === '1' ? false : deleteLink }
      </li>
    );
  }
}

export default TemplateBox;
