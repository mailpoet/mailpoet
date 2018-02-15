import React from 'react';
import _ from 'underscore';
import MailPoet from 'mailpoet';
import { confirmAlert } from 'react-confirm-alert';
import classNames from 'classnames';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import HelpTooltip from 'help-tooltip.jsx';

const editorURL = id => `admin.php?page=mailpoet-newsletter-editor&id=${id}`;

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
        <li key={ name }><a
          href="javascript:"
          className={selected === name ? 'current' : ''}
          onClick={() => select(name)}
          > {label}
        </a></li>
      ))}
    </ul>
  </div>
);

class TemplateBox extends React.Component {
  constructor({
    index, id, newsletterId, name, description, 
    thumbnail, readonly, setLoading, afterDelete
  }) {
    super()
    this.props = {
      index, id, newsletterId, name, description, 
      thumbnail, readonly, setLoading, afterDelete
    };
    this.onDelete = this.onDelete.bind(this);
    this.onPreview = this.onPreview.bind(this);
    this.onSelect = this.onSelect.bind(this);
  }

  onDelete() {
    const {id, index, name, setLoading, afterDelete} = this.props;
    const onConfirm = () => {
      setLoading(true);
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'newsletterTemplates',
        action: 'delete',
        data: {
          id: id,
        },
      }).done(() => {
        afterDelete(true, index);
      }).fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(error => error.message),
            { scroll: true }
          );
        }
        afterDelete(false, index);
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
    const {newsletterId, name, setLoading} = this.props;
    let body = this.props.body;

    if (!_.isUndefined(body)) {
      body = JSON.stringify(body);
    }

    setLoading(true);
    
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
      window.location = editorURL(response.data.id);
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
      setLoading(false);
    });
  }

  render() {
    const {index, name, thumbnail, description, readonly} = this.props
    
    const deleteLink = (
      <div className="mailpoet_delete">
        <a href="javascript:;" onClick={this.onDelete}>{MailPoet.I18n.t('delete')}</a>
      </div>
    );

    let preview = '';
    if (typeof thumbnail === 'string' && thumbnail.length > 0) {
      preview = (
        <a href="javascript:;" onClick={this.onPreview}>
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
            onClick={this.onPreview}
          >{MailPoet.I18n.t('preview')}</a>
            &nbsp;
          <a
            className="button button-primary"
            data-automation-id={`select_template_${index}`}
            onClick={this.onSelect}
            > {MailPoet.I18n.t('select')} </a>
        </div>
        { readonly === '1' ? false : deleteLink }
      </li>
    );
  }
}

class ImportTemplate extends React.Component {
  constructor({setLoading, afterImport}) {
    super();
    this.props = {setLoading, afterImport};
  }

  saveTemplate(saveTemplate) {
    const template = saveTemplate;

    // Stringify to enable transmission of primitive non-string value types
    if (!_.isUndefined(template.body)) {
      template.body = JSON.stringify(template.body);
    }

    if (undefined === template.categories) {
      template.categories = '["saved"]';
    }

    setLoading(true);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletterTemplates',
      action: 'save',
      data: template,
    }).done((response) => {
      this.props.afterImport(true, response.data);
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
      this.props.afterImport(false);
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
  constructor() {
    super();
    this.state = {
      loading: true,
      templates: {}, // {category1: [template11, template12, ..], category2: [template21, ...]}
      selectedTab: '',
    };
    this.afterTemplateDelete = this.afterTemplateDelete.bind(this);
    this.afterTemplateImport = this.afterTemplateImport.bind(this);
  }

  componentWillMount() {
    const templates = {};
    const categoriesNames = templatesCategories.map(category => category.name);
    categoriesNames.forEach((name) => {
      templates[name] = [];
    });

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
      response.data.forEach((template) => {
        let categories;
        try {
          categories = JSON.parse(template.categories)
            .filter(name => categoriesNames.indexOf(name) !== -1);
        } catch (err) {
          categories = [];
        }
        if (categories.length === 0) { // the template has no known category
          categories = ['saved'];     // we add it to "Your saved templates"
        }
        categories.forEach((category) => {
          templates[category].push(template);
        });
      });
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    }).always(() => {
      this.selectInitialCategory(templates);
    });
  }

  selectInitialCategory(templates) {
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
        templates: templates,
        selectedTab: selectedTab,
        loading: false,
      });
    });
  }

  afterTemplateDelete(success, index) {
    let templates = this.state.templates;
    if (success) {
      templates[this.state.selectedTab].splice(index, 1);
    }
    this.setState({
      templates: templates,
      loading: false,
    });
  }

  afterTemplateImport(success, template) {
    let templates = this.state.templates;
    if (success) {
      // WIP ...
    }
    this.setState({
      templates: templates,
      loading: false,
    })
  }

  render() {
    const tabs = templatesCategories.concat({
      name: 'import',
      label: MailPoet.I18n.t('tabImportTitle'),
    });

    const selectedTab = this.state.selectedTab;
    let content = null;
    if (selectedTab === 'import') {
      content = <ImportTemplate
        afterImport={this.afterTemplateImport}
        setLoading={value => this.setState({ loading: value })}
      />;
    } else {
      let templates = this.state.templates[this.state.selectedTab] || [];
      if (templates.length === 0) {
        templates = <p>{MailPoet.I18n.t('noTemplates')}</p>;
      } else {
        templates = templates.map((template, index) =>
          <TemplateBox 
            key={index}
            index={index}
            newsletterId={this.props.params.id}
            afterDelete={this.afterTemplateDelete}
            setLoading={value => this.setState({ loading: value })}
            {...template}
          />
        )
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
          select={name => this.setState({selectedTab: name})}
        />

        {content}

      </div>
    );
  }
}

export default NewsletterTemplates;
