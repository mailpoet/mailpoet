import { Component } from 'react';
import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';

import { Button } from 'common/button/button';
import { TemplateBox as TemplateBoxWrap } from 'common/template_box/template_box';
import { confirmAlert } from 'common/confirm_alert.jsx';
import { GlobalContext } from 'context/index.jsx';

/**
 * props = {
 *   index, id, newsletterId, name, description, thumbnail, readonly,
 *   beforeDelete, afterDelete, beforeSelect, afterSelect
 * }
 */
class TemplateBox extends Component {
  constructor(props) {
    super(props);
    this.onPreview = this.onPreview.bind(this);
    this.onDelete = this.onDelete.bind(this);
    this.onSelect = this.onSelect.bind(this);
  }

  onDelete() {
    const { id, name, beforeDelete, afterDelete } = this.props;
    const onConfirm = () => {
      beforeDelete();
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'newsletterTemplates',
        action: 'delete',
        data: {
          id,
        },
      })
        .done(() => {
          afterDelete(true, id);
        })
        .fail((response) => {
          if (response.errors.length > 0) {
            this.context.notices.error(
              response.errors.map((error) => (
                <p key={error.message}>{error.message}</p>
              )),
              { scroll: true },
            );
          }
          afterDelete(false);
        });
    };
    confirmAlert({
      message: MailPoet.I18n.t('confirmTemplateDeletion').replace('%1$s', name),
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
    const { newsletterId, name, beforeSelect, afterSelect } = this.props;

    beforeSelect();

    MailPoet.trackEvent('Emails > Template selected', {
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
    })
      .done((response) => {
        afterSelect(true, response.data.id);
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          this.context.notices.error(
            response.errors.map((error) => (
              <p key={error.message}>{error.message}</p>
            )),
            { scroll: true },
          );
        }
        afterSelect(false);
      });
  }

  render() {
    const { index, name, thumbnail, readonly } = this.props;

    let preview = '';
    if (typeof thumbnail === 'string' && thumbnail.length > 0) {
      preview = (
        <a
          className="mailpoet-template-preview"
          href="#"
          onClick={(event) => {
            event.preventDefault();
            this.onPreview(event);
          }}
        >
          <div className="mailpoet-template-thumbnail">
            {thumbnail ? (
              <img
                src={thumbnail}
                alt={MailPoet.I18n.t('templatePreview')}
                loading="lazy"
              />
            ) : (
              ''
            )}
          </div>
          <div className="mailpoet-template-preview-overlay">
            <Button>{MailPoet.I18n.t('zoom')}</Button>
          </div>
        </a>
      );
    }

    return (
      <TemplateBoxWrap
        label={name}
        onSelect={this.onSelect}
        onDelete={readonly === false ? this.onDelete : undefined}
        automationId={`select_template_${index}`}
        className="mailpoet-template-two-lines"
      >
        {preview}
      </TemplateBoxWrap>
    );
  }
}

TemplateBox.contextType = GlobalContext;

TemplateBox.propTypes = {
  index: PropTypes.number.isRequired,
  id: PropTypes.number.isRequired,
  newsletterId: PropTypes.string.isRequired,
  name: PropTypes.string.isRequired,
  thumbnail: PropTypes.string,
  readonly: PropTypes.bool.isRequired,
  beforeDelete: PropTypes.func.isRequired,
  afterDelete: PropTypes.func.isRequired,
  beforeSelect: PropTypes.func.isRequired,
  afterSelect: PropTypes.func.isRequired,
};

TemplateBox.defaultProps = {
  thumbnail: null,
};
TemplateBox.displayName = 'TemplateBox';
export { TemplateBox };
