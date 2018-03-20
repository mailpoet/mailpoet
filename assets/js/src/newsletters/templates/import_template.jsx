import React from 'react';
import _ from 'underscore';
import MailPoet from 'mailpoet';
import HelpTooltip from 'help-tooltip.jsx';

/**
 * props = {beforeImport, afterImport}
 */
class ImportTemplate extends React.Component {
  constructor(props) {
    super(props);
    this.handleSubmit = this.handleSubmit.bind(this);
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
    }).done((response) => {
      afterImport(true, response.data);
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
      afterImport(false);
    });
  }

  handleSubmit(e) {
    e.preventDefault();

    if (_.size(this.templateFile.files) <= 0) {
      return false;
    }

    const file = _.first(this.templateFile.files);
    const reader = new FileReader();

    reader.onload = (evt) => {
      try {
        this.saveTemplate(JSON.parse(evt.target.result));
        MailPoet.trackEvent('Emails > Template imported', {
          'MailPoet Free version': window.mailpoet_version,
        });
      } catch (err) {
        MailPoet.Notice.error(MailPoet.I18n.t('templateFileMalformedError'));
      }
    };

    reader.readAsText(file);
    return true;
  }
  render() {
    return (
      <div>
        <h2>
          {MailPoet.I18n.t('importTemplateTitle')}
          <HelpTooltip
            tooltip={MailPoet.I18n.t('helpTooltipTemplateUpload')}
            place="right"
            className="tooltip-help-import-template"
          />
        </h2>
        <form onSubmit={this.handleSubmit}>
          <input
            type="file"
            placeholder={MailPoet.I18n.t('selectJsonFileToUpload')}
            ref={(c) => { this.templateFile = c; }}
          />
          <p className="submit">
            <input
              className="button button-primary"
              type="submit"
              value={MailPoet.I18n.t('upload')}
            />
          </p>
        </form>
      </div>
    );
  }
}

export default ImportTemplate;
