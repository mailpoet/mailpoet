import { createRef, Component } from 'react';
import _ from 'underscore';
import MailPoet from 'mailpoet';
import HelpTooltip from 'help-tooltip.jsx';
import PropTypes from 'prop-types';
import { GlobalContext } from 'context/index.jsx';

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
          <p>{MailPoet.I18n.t('templateFileMalformedError')}</p>,
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
          this.context.notices.error(
            response.errors.map((error) => (
              <p key={error.message}>{error.message}</p>
            )),
            { scroll: true },
          );
        }
        afterImport(false);
      });
  }

  render() {
    return (
      <div className="mailpoet-template-import">
        <h4>
          {MailPoet.I18n.t('importTemplateTitle')}
          <HelpTooltip
            tooltip={MailPoet.I18n.t('helpTooltipTemplateUpload')}
            place="right"
            className="tooltip-help-import-template"
          />
        </h4>
        <form onSubmit={this.handleSubmit}>
          <input
            type="file"
            placeholder={MailPoet.I18n.t('selectJsonFileToUpload')}
            ref={this.fileRef}
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

ImportTemplate.contextType = GlobalContext;

ImportTemplate.propTypes = {
  beforeImport: PropTypes.func.isRequired,
  afterImport: PropTypes.func.isRequired,
};

export default ImportTemplate;
