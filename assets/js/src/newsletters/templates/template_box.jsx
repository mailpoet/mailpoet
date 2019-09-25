import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

import confirmAlert from 'common/confirm_alert.jsx';

/**
 * props = {
 *   index, id, newsletterId, name, description, thumbnail, readonly,
 *   beforeDelete, afterDelete, beforeSelect, afterSelect
 * }
 */
class TemplateBox extends React.Component {
  constructor(props) {
    super(props);
    this.onPreview = this.onPreview.bind(this);
    this.onDelete = this.onDelete.bind(this);
    this.onSelect = this.onSelect.bind(this);
  }

  onDelete() {
    const {
      id, name, beforeDelete, afterDelete,
    } = this.props;
    const onConfirm = () => {
      beforeDelete();
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'newsletterTemplates',
        action: 'delete',
        data: {
          id,
        },
      }).done(() => {
        afterDelete(true, id);
      }).fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true }
          );
        }
        afterDelete(false);
      });
    };
    confirmAlert({
      message: MailPoet.I18n.t('confirmTemplateDeletion').replace('%$1s', name),
      onConfirm,
    });
  }

  onPreview() {
    MailPoet.Modal.popup({
      title: this.props.name,
      template: '<img src="{{ thumbnail }}" />',
      data: this.props,
    });
  }

  onSelect() {
    const {
      newsletterId, name, beforeSelect, afterSelect,
    } = this.props;

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
        template_id: this.props.id,
      },
    }).done((response) => {
      afterSelect(true, response.data.id);
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true }
        );
      }
      afterSelect(false);
    });
  }

  render() {
    const {
      index, name, thumbnail, readonly,
    } = this.props;

    const deleteLink = (
      <button className="mailpoet_delete button button-secondary" onClick={this.onDelete} type="button">
        {MailPoet.I18n.t('delete')}
      </button>
    );

    let preview = '';
    if (typeof thumbnail === 'string' && thumbnail.length > 0) {
      preview = (
        <a href="javascript:;" onClick={this.onPreview}>
          <img src={thumbnail} alt={MailPoet.I18n.t('templatePreview')} loading="lazy" />
          <div className="mailpoet_overlay">
            <p className="mailpoet_more_details">{MailPoet.I18n.t('zoom')}</p>
          </div>
        </a>
      );
    }

    return (
      <li className="mailpoet_template_boxes" data-automation-id="select_template_box">
        <div className="mailpoet_thumbnail">
          { preview }
        </div>

        <div className="mailpoet_template_content">
          <div className="mailpoet_description">
            <h3>{ name }</h3>
          </div>

          <div className="mailpoet_actions">
            { readonly === '1' ? false : deleteLink }
            <a
              className="button button-primary"
              data-automation-id={`select_template_${index}`}
              onClick={this.onSelect}
              role="button"
              tabIndex={0}
              onKeyDown={(event) => {
                if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
                ) {
                  event.preventDefault();
                  this.onSelect();
                }
              }}
            >
              {' '}
              {MailPoet.I18n.t('select')}
              {' '}

            </a>
          </div>
        </div>
      </li>
    );
  }
}

TemplateBox.propTypes = {
  index: PropTypes.number.isRequired,
  id: PropTypes.string.isRequired,
  newsletterId: PropTypes.string.isRequired,
  name: PropTypes.string.isRequired,
  thumbnail: PropTypes.string.isRequired,
  readonly: PropTypes.string.isRequired,
  beforeDelete: PropTypes.func.isRequired,
  afterDelete: PropTypes.func.isRequired,
  beforeSelect: PropTypes.func.isRequired,
  afterSelect: PropTypes.func.isRequired,
};

export default TemplateBox;
