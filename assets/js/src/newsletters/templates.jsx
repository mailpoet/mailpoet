import React from 'react';
import _ from 'underscore';
import MailPoet from 'mailpoet';
import { confirmAlert } from 'react-confirm-alert';
import classNames from 'classnames';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import HelpTooltip from 'help-tooltip.jsx';

const ImportTemplate = React.createClass({
  saveTemplate: function (saveTemplate) {
    const template = saveTemplate;

    // Stringify to enable transmission of primitive non-string value types
    if (!_.isUndefined(template.body)) {
      template.body = JSON.stringify(template.body);
    }

    MailPoet.Modal.loading(true);

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletterTemplates',
      action: 'save',
      data: template,
    }).always(() => {
      MailPoet.Modal.loading(false);
    }).done((response) => {
      this.props.onImport(response.data);
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    });
  },
  handleSubmit: function (e) {
    e.preventDefault();

    if (_.size(this.refs.templateFile.files) <= 0) return false;


    const file = _.first(this.refs.templateFile.files);
    const reader = new FileReader();
    const saveTemplate = this.saveTemplate;

    reader.onload = (evt) => {
      try {
        saveTemplate(JSON.parse(evt.target.result));
        MailPoet.trackEvent('Emails > Template imported', {
          'MailPoet Free version': window.mailpoet_version,
        });
      } catch (err) {
        MailPoet.Notice.error(MailPoet.I18n.t('templateFileMalformedError'));
      }
    };

    reader.readAsText(file);
    return true;
  },
  render: function () {
    return (
      <div>
        <h2>{MailPoet.I18n.t('importTemplateTitle')} <HelpTooltip
          tooltip={MailPoet.I18n.t('helpTooltipTemplateUpload')}
          place="right"
          className="tooltip-help-import-template"
        /></h2>
        <form onSubmit={this.handleSubmit}>
          <input type="file" placeholder={MailPoet.I18n.t('selectJsonFileToUpload')} ref="templateFile" />
          <p className="submit">
            <input
              className="button button-primary"
              type="submit"
              value={MailPoet.I18n.t('upload')} />
          </p>
        </form>
      </div>
    );
  },
});

const NewsletterTemplates = React.createClass({
  getInitialState: function () {
    return {
      loading: false,
      templates: [],
    };
  },
  componentDidMount: function () {
    this.getTemplates();
  },
  getTemplates: function () {
    this.setState({ loading: true });

    MailPoet.Modal.loading(true);

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletterTemplates',
      action: 'getAll',
    }).always(() => {
      MailPoet.Modal.loading(false);
    }).done((response) => {
      if (this.isMounted()) {
        if (response.data.length === 0) {
          response.data = [
            {
              name:
                MailPoet.I18n.t('mailpoetGuideTemplateTitle'),
              description:
                MailPoet.I18n.t('mailpoetGuideTemplateDescription'),
              readonly: '1',
            },
          ];
        }
        this.setState({
          templates: response.data,
          loading: false,
        });
      }
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    });
  },
  handleSelectTemplate: function (template) {
    let body = template.body;

    // Stringify to enable transmission of primitive non-string value types
    if (!_.isUndefined(body)) {
      body = JSON.stringify(body);
    }

    MailPoet.trackEvent('Emails > Template selected', {
      'MailPoet Free version': window.mailpoet_version,
      'Email name': template.name,
    });

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'save',
      data: {
        id: this.props.params.id,
        body: body,
      },
    }).done((response) => {
      // TODO: Move this URL elsewhere
      window.location = `admin.php?page=mailpoet-newsletter-editor&id=${response.data.id}`;
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    });
  },
  handleDeleteTemplate: function (template) {
    this.setState({ loading: true });
    const onConfirm = () => {
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'newsletterTemplates',
        action: 'delete',
        data: {
          id: template.id,
        },
      }).done(() => {
        this.getTemplates();
      }).fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(error => error.message),
            { scroll: true }
          );
        }
      });
    };
    const onCancel = () => {
      this.setState({ loading: false });
    };
    confirmAlert({
      title: MailPoet.I18n.t('confirmTitle'),
      message: MailPoet.I18n.t('confirmTemplateDeletion').replace('%$1s', template.name),
      confirmLabel: MailPoet.I18n.t('confirmLabel'),
      cancelLabel: MailPoet.I18n.t('cancelLabel'),
      onConfirm: onConfirm,
      onCancel: onCancel,
    });
  },
  handleShowTemplate: function (template) {
    MailPoet.Modal.popup({
      title: template.name,
      template: '<div class="mailpoet_boxes_preview" style="background-color: {{ body.globalStyles.body.backgroundColor }}"><img src="{{ thumbnail }}" /></div>',
      data: template,
    });
  },
  handleTemplateImport: function () {
    this.getTemplates();
  },
  render: function () {
    const templates = this.state.templates.map((template, index) => {
      const deleteLink = (
        <div className="mailpoet_delete">
          <a
            href="javascript:;"
            onClick={this.handleDeleteTemplate.bind(null, template)}
          >
            {MailPoet.I18n.t('delete')}
          </a>
        </div>
      );
      let thumbnail = '';

      if (typeof template.thumbnail === 'string'
          && template.thumbnail.length > 0) {
        thumbnail = (
          <a href="javascript:;" onClick={this.handleShowTemplate.bind(null, template)}>
            <img src={template.thumbnail} />
            <div className="mailpoet_overlay"></div>
          </a>
        );
      }

      return (
        <li key={`template-${index}`}>
          <div className="mailpoet_thumbnail">
            { thumbnail }
          </div>

          <div className="mailpoet_description">
            <h3>{ template.name }</h3>
            <p>{ template.description }</p>
          </div>

          <div className="mailpoet_actions">
            <a
              className="button button-secondary"
              onClick={this.handleShowTemplate.bind(null, template)}
              >
              {MailPoet.I18n.t('preview')}
            </a>
              &nbsp;
            <a
              className="button button-primary"
              data-automation-id={`select_template_${index}`}
              onClick={this.handleSelectTemplate.bind(null, template)}
              >
              {MailPoet.I18n.t('select')}
            </a>
          </div>
          { (template.readonly === '1') ? false : deleteLink }
        </li>
      );
    });

    const boxClasses = classNames(
      'mailpoet_boxes',
      'clearfix',
      { mailpoet_boxes_loading: this.state.loading }
    );

    return (
      <div>
        <h1>{MailPoet.I18n.t('selectTemplateTitle')}</h1>

        <Breadcrumb step="template" />

        <ul className={boxClasses}>
          { templates }
        </ul>

        <ImportTemplate onImport={this.handleTemplateImport} />
      </div>
    );
  },
});

export default NewsletterTemplates;
