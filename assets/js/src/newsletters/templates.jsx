import React from 'react';
import _ from 'underscore';
import MailPoet from 'mailpoet';
import { confirmAlert } from 'react-confirm-alert';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import HelpTooltip from 'help-tooltip.jsx';

const getEditorUrl = id => `admin.php?page=mailpoet-newsletter-editor&id=${id}`;

const templatesCategories = [
  {
    name: 'standard',
    label: MailPoet.I18n.t('tabStandardTitle'),
  },
  {
    name: 'welcome',
    label: MailPoet.I18n.t('tabWelcomeTitle'),
  },
  {
    name: 'notification',
    label: MailPoet.I18n.t('tabNotificationTitle'),
  },
  {
    name: 'sample',
    label: MailPoet.I18n.t('sample'),
  },
  {
    name: 'blank',
    label: MailPoet.I18n.t('blank'),
  },
  {
    name: 'recent',
    label: MailPoet.I18n.t('recentlySent'),
  },
  {
    name: 'saved',
    label: MailPoet.I18n.t('savedTemplates'),
  },
];

class Loading extends React.Component {
  componentWillMount() {
    MailPoet.Modal.loading(true);
  }
  componentWillUnmount() {
    MailPoet.Modal.loading(false);
  }
  render() {
    return null;
  }
}

const Tabs = ({ tabs, selected, select }) => (
  <div className="wp-filter hide-if-no-js">
    <ul className="filter-links">
      {tabs.map(({ name, label }) => (
        <li key={name}><a
          href="javascript:"
          className={selected === name ? 'current' : ''}
          onClick={() => select(name)}
          > {label}
        </a></li>
      ))}
    </ul>
  </div>
);

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
    const { newsletterId, name, beforeSelect } = this.props;
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

/**
 * props = {beforeImport, afterImport}
 */
class ImportTemplate extends React.Component {

  saveTemplate(saveTemplate) {
    const template = saveTemplate;
    const {beforeImport, afterImport} = this.props;

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

    if (_.size(this.refs.templateFile.files) <= 0) {
      return false;
    }

    const file = _.first(this.refs.templateFile.files);
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
    const handleSubmit = this.handleSubmit.bind(this);
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
        <form onSubmit={handleSubmit}>
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
  }
}

class NewsletterTemplates extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      loading: true,
      templates: {}, // {category1: [template11, template12, ..], category2: [template21, ...]}
      selectedTab: '',
    };
    this.templates = {};
  }

  componentWillMount() {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletterTemplates',
      action: 'getAll',
    }).done((response) => {
      if (response.data.length === 0) {
        response.data = [
          {
            name:
              MailPoet.I18n.t('mailpoetGuideTemplateTitle'),
            description:
              MailPoet.I18n.t('mailpoetGuideTemplateDescription'),
            categories: '["welcome", "notification", "standard"]',
            readonly: '1',
          },
        ];
      }
      response.data.forEach(this.addTemplate.bind(this));
      this.sortTemplates();
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    }).always(() => {
      this.selectInitialTab();
    });
  }

  addTemplate(template) {
    const categoriesNames = templatesCategories.map(category => category.name);
    let categories;

    try {
      categories = JSON.parse(template.categories)
        .filter(name => categoriesNames.indexOf(name) !== -1);
    } catch (err) {
      categories = [];
    }

    // the template has no known category
    // we add it to "Your saved templates"
    if (categories.length === 0) {
      categories.push('saved');
    }

    categories.forEach((category) => {
      if (this.templates[category] === undefined) {
        this.templates[category] = [];
      }
      this.templates[category].unshift(template);
    });
  }

  sortTemplates() {
    Object.keys(this.templates).forEach((category) => {
      this.templates[category].sort((a, b) => {
        if (parseInt(a.id, 10) < parseInt(b.id, 10)) {
          return 1;
        }
        return -1;
      });
    });
  }

  selectInitialTab() {
    let selectedTab = 'standard';
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: {
        id: this.props.params.id,
      },
    }).done((response) => {
      selectedTab = response.data.type;
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    }).always(() => {
      this.setState({
        templates: this.templates,
        selectedTab: selectedTab,
        loading: false,
      });
    });
  }

  afterTemplateDelete(success, id) {
    if (success) {
      Object.keys(this.templates).forEach((category) => {
        this.templates[category] = this.templates[category].filter(template => template.id !== id);
      });
    }
    this.setState({
      templates: this.templates,
      loading: false,
    });
  }

  afterTemplateSelect(success, id) {
    if (success) {
      window.location = getEditorUrl(id);
    } else {
      this.setState({ loading: false });
    }
  }

  afterTemplateImport(success, template) {
    if (success) {
      this.addTemplate(template);
    }
    this.setState({
      templates: this.templates,
      selectedTab: success ? 'saved' : 'import',
      loading: false,
    });
  }

  render() {
    const afterTemplateDelete = this.afterTemplateDelete.bind(this);
    const afterTemplateSelect = this.afterTemplateSelect.bind(this);
    const afterTemplateImport = this.afterTemplateImport.bind(this);

    const tabs = templatesCategories.concat({
      name: 'import',
      label: MailPoet.I18n.t('tabImportTitle'),
    });

    const selectedTab = this.state.selectedTab;
    let content = null;
    if (selectedTab === 'import') {
      content = (
        <ImportTemplate
          beforeImport={() => this.setState({ loading: true })}
          afterImport={afterTemplateImport}
        />
      );
    } else {
      let templates = this.state.templates[this.state.selectedTab] || [];
      if (templates.length === 0) {
        templates = <p>{MailPoet.I18n.t('noTemplates')}</p>;
      } else {
        templates = templates.map((template, index) => (
          <TemplateBox
            key={index}
            index={index}
            newsletterId={this.props.params.id}
            beforeDelete={() => this.setState({ loading: true })}
            afterDelete={afterTemplateDelete}
            beforeSelect={() => this.setState({ loading: true })}
            afterSelect={afterTemplateSelect}
            {...template}
          />
        ));
      }
      content = <ul className="mailpoet_boxes clearfix">{templates}</ul>;
    }

    return (
      <div>
        {this.state.loading && <Loading />}

        <h1>{MailPoet.I18n.t('selectTemplateTitle')}</h1>

        <Breadcrumb step="template" />

        <Tabs
          tabs={tabs}
          selected={this.state.selectedTab}
          select={name => this.setState({ selectedTab: name })}
        />

        {content}

      </div>
    );
  }
}

export default NewsletterTemplates;
