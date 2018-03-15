import React from 'react';
import MailPoet from 'mailpoet';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import Loading from 'common/loading.jsx';
import Tabs from 'newsletters/templates/tabs.jsx';
import TemplateBox from 'newsletters/templates/template_box.jsx';
import ImportTemplate from 'newsletters/templates/import_template.jsx';

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

class NewsletterTemplates extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      loading: true,
      templates: {}, // {category1: [template11, template12, ..], category2: [template21, ...]}
      selectedTab: '',
    };
    this.templates = {};

    this.addTemplate = this.addTemplate.bind(this);
    this.afterTemplateDelete = this.afterTemplateDelete.bind(this);
    this.afterTemplateSelect = this.afterTemplateSelect.bind(this);
    this.afterTemplateImport = this.afterTemplateImport.bind(this);
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
      response.data.forEach(this.addTemplate);
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
        selectedTab,
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
          afterImport={this.afterTemplateImport}
        />
      );
    } else {
      let templates = this.state.templates[this.state.selectedTab] || [];
      if (templates.length === 0) {
        if (this.state.loading) {
          templates = null;
        } else {
          templates = <p>{MailPoet.I18n.t('noTemplates')}</p>;
        }
      } else {
        templates = templates.map((template, index) => (
          <TemplateBox
            key={index}
            index={index}
            newsletterId={this.props.params.id}
            beforeDelete={() => this.setState({ loading: true })}
            afterDelete={this.afterTemplateDelete}
            beforeSelect={() => this.setState({ loading: true })}
            afterSelect={this.afterTemplateSelect}
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
