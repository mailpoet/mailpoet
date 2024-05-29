import { createRef, Component } from 'react';
import _ from 'underscore';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Tooltip } from 'help-tooltip.jsx';
import PropTypes from 'prop-types';
import { GlobalContext } from 'context';

class ImportTemplate extends Component {
  constructor(props) {
    super(props);
    this.fileRef = createRef();
    this.handleSubmit = this.handleSubmit.bind(this);
  }

  handleSubmit(e) {
    e.preventDefault();

    if (_.size(this.fileRef.current.files) <= 0) {
      return false;
    }

    const file = _.first(this.fileRef.current.files);
    const reader = new FileReader();

    reader.onload = (evt) => {
      try {
        this.saveTemplate(JSON.parse(evt.target.result));
        MailPoet.trackEvent('Emails > Template imported');
      } catch (err) {
        this.context.notices.error(
          <p>
            {__(
              'This template file appears to be damaged. Please try another one.',
              'mailpoet',
            )}
          </p>,
        );
      }
    };

    reader.readAsText(file);
    return true;
  }

  saveTemplate(saveTemplate) {
    const template = saveTemplate;
    const { beforeImport, afterImport } = this.props;

    // Stringify to enable transmission of primitive non-string value types
    if (!_.isUndefined(template.body)) {
      template.body = JSON.stringify(template.body);
    }

    try {
      template.categories = JSON.parse(template.categories);
    } catch (err) {
      template.categories = [];
    }

    if (template.categories.indexOf('saved') === -1) {
      template.categories.push('saved');
    }

    if (
      template.categories.indexOf('standard') === -1 &&
      template.categories.indexOf('welcome') === -1 &&
      template.categories.indexOf('notification') === -1
    ) {
      template.categories.push('standard');
    }

    template.categories = JSON.stringify(template.categories);

    beforeImport();
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletterTemplates',
      action: 'save',
      data: template,
    })
      .done((response) => {
        afterImport(true, response.data);
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          this.context.notices.apiError(response, { scroll: true });
        }
        afterImport(false);
      });
  }

  render() {
    return (
      <div className="mailpoet-template-import">
        <h4>
          {__('Import a template', 'mailpoet')}
          <Tooltip
            tooltip={__(
              'You can only upload .json templates that were originally created with MailPoet.',
              'mailpoet',
            )}
            place="right"
            className="tooltip-help-import-template"
          />
        </h4>
        <form onSubmit={this.handleSubmit}>
          <input
            type="file"
            placeholder={__('Select a .json file to upload', 'mailpoet')}
            ref={this.fileRef}
          />
          <p className="submit">
            <input
              className="button button-primary"
              type="submit"
              value={__('Upload', 'mailpoet')}
            />
          </p>
        </form>
      </div>
    );
  }
}

ImportTemplate.contextType = GlobalContext;

ImportTemplate.propTypes = {
  beforeImport: PropTypes.func.isRequired,
  afterImport: PropTypes.func.isRequired,
};

export { ImportTemplate };
