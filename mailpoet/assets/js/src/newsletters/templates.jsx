import _ from 'underscore';
import { Component } from 'react';
import PropTypes from 'prop-types';
import { __, _x } from '@wordpress/i18n';

import { Background } from 'common/background/background';
import { Categories } from 'common/categories/categories';
import { GlobalContext } from 'context';
import { ListingHeadingStepsRoute } from 'newsletters/listings/heading-steps-route';
import { Loading } from 'common/loading.jsx';
import { MailPoet } from 'mailpoet';
import { TemplateBox } from 'newsletters/templates/template-box.jsx';
import { ImportTemplate } from 'newsletters/templates/import-template.jsx';
import { ErrorBoundary } from '../common';

const getEditorUrl = (id) => {
  const context = new URLSearchParams(window.location.search).get('context');
  const url = `admin.php?page=mailpoet-newsletter-editor&id=${id}`;
  return context ? `${url}&context=${context}` : url;
};

const templatesCategories = [];
if (window.mailpoet_newsletters_templates_recently_sent_count) {
  templatesCategories.push({
    name: 'recent',
    label: __('Recently sent', 'mailpoet'),
  });
}

templatesCategories.push(
  ...[
    {
      name: 'standard',
      label: __('Newsletters', 'mailpoet'),
    },
    {
      name: 'welcome',
      label: __('Welcome Emails', 'mailpoet'),
    },
    {
      name: 'notification',
      label: __('Post Notifications', 'mailpoet'),
    },
    {
      name: 're_engagement',
      label: __('Re-engagement Emails', 'mailpoet'),
    },
    {
      name: 'blank',
      label: __('Simple text', 'mailpoet'),
    },
  ],
);

if (window.mailpoet_woocommerce_active) {
  templatesCategories.push({
    name: 'woocommerce',
    label: __('WooCommerce Emails', 'mailpoet'),
  });
}

templatesCategories.push(
  ...[
    {
      name: 'saved',
      label: __('Your saved templates', 'mailpoet'),
    },
  ],
);

class NewsletterTemplates extends Component {
  constructor(props) {
    super(props);
    this.state = {
      loading: true,
      templates: {}, // {category1: [template11, template12, ..], category2: [template21, ...]}
      emailType: null,
      emailOptions: {},
      selectedTab: '',
    };
    this.templates = {};

    this.addTemplate = this.addTemplate.bind(this);
    this.afterTemplateDelete = this.afterTemplateDelete.bind(this);
    this.afterTemplateSelect = this.afterTemplateSelect.bind(this);
    this.afterTemplateImport = this.afterTemplateImport.bind(this);
  }

  componentDidMount() {
    MailPoet.Ajax.get({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletterTemplates',
      action: 'getAll',
    })
      .done((response) => {
        if (response.data.length === 0) {
          response.data = [
            {
              name: __("MailPoet's Guide", 'mailpoet'),
              categories:
                '["welcome", "notification", "standard", "woocommerce"]',
              readonly: true,
            },
          ];
        }
        response.data.forEach(this.addTemplate);
        this.sortTemplates();
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          this.context.notices.apiError(response, { scroll: true });
        }
      })
      .always(() => {
        this.selectInitialTab();
      });
  }

  addTemplate(template) {
    const categoriesNames = templatesCategories.map(
      (category) => category.name,
    );
    let categories;

    if (categoriesNames.indexOf('woocommerce') === -1) {
      categoriesNames.push('woocommerce');
    }
    try {
      categories = JSON.parse(template.categories).filter(
        (name) => categoriesNames.indexOf(name) !== -1,
      );
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
    const blankFirstCategories = ['welcome', 'notification', 'standard'];
    Object.keys(this.templates).forEach((category) => {
      this.templates[category].sort((a, b) => {
        // MAILPOET-2087 - templates of type "blank" should be first
        if (blankFirstCategories.includes(category)) {
          if (
            a.categories.includes('"blank"') &&
            !b.categories.includes('"blank"')
          ) {
            return -1;
          }
          if (
            !a.categories.includes('"blank"') &&
            b.categories.includes('"blank"')
          ) {
            return 1;
          }
        }
        if (a.id < b.id) {
          return 1;
        }
        return -1;
      });
    });
  }

  selectInitialTab() {
    let emailType;
    let emailOptions;
    let selectedTab = 'standard';
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: {
        id: this.props.match.params.id,
      },
    })
      .done((response) => {
        emailType = response.data.type;
        emailOptions = response.data.options;
        if (emailType === 'automatic') {
          emailType = response.data.options.group || emailType;
        }
        if (window.mailpoet_newsletters_templates_recently_sent_count) {
          selectedTab = 'recent';
        } else if (
          _.findWhere(templatesCategories, { name: response.data.type })
        ) {
          selectedTab = response.data.type;
        } else if (
          response.data.type === 'automatic' &&
          _.findWhere(templatesCategories, {
            name: response.data.options.group,
          })
        ) {
          selectedTab = response.data.options.group;
        }
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          this.context.notices.apiError(response, { scroll: true });
        }
      })
      .always(() => {
        this.setState({
          templates: this.templates,
          emailType,
          emailOptions,
          selectedTab,
          loading: false,
        });
      });
  }

  afterTemplateDelete(success, id) {
    if (success) {
      Object.keys(this.templates).forEach((category) => {
        this.templates[category] = this.templates[category].filter(
          (template) => template.id !== id,
        );
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
    if (this.state.loading) return <Loading />;

    const categories = templatesCategories
      .concat({
        name: 'import',
        label: _x('Import', 'Importing template tab title', 'mailpoet'),
      })
      .map((category) =>
        Object.assign(category, {
          automationId: `templates-${category.name
            .replace(/\s+/g, '-')
            .toLowerCase()}`,
        }),
      );

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
          templates = (
            <p>
              {__(
                'This category does not contain any template yet!',
                'mailpoet',
              )}
            </p>
          );
        }
      } else {
        templates = templates.map((template, index) => (
          <TemplateBox
            key={template.id}
            index={index}
            newsletterId={this.props.match.params.id}
            beforeDelete={() => this.setState({ loading: true })}
            afterDelete={this.afterTemplateDelete}
            beforeSelect={() => this.setState({ loading: true })}
            afterSelect={this.afterTemplateSelect}
            id={template.id}
            name={template.name}
            thumbnail={template.thumbnail}
            readonly={template.readonly}
          />
        ));
      }
      content = templates;
    }

    let buttons = null;
    let onClick;
    if (this.state.emailType === 'automation') {
      const automationId = this.state.emailOptions?.automationId;
      const goToUrl = automationId
        ? `admin.php?page=mailpoet-automation-editor&id=${automationId}`
        : 'admin.php?page=mailpoet-automation';
      onClick = () => {
        window.location = goToUrl;
      };
      buttons = (
        <input
          type="button"
          className="button link-button"
          onClick={onClick}
          value="Cancel"
        />
      );
    }

    return (
      <div>
        <Background color="#fff" />

        <ListingHeadingStepsRoute
          emailType={this.state.emailType}
          automationId="email_template_selection_heading"
          buttons={buttons}
          onLogoClick={onClick}
        />

        <div className="mailpoet-templates">
          <ErrorBoundary>
            <Categories
              categories={categories}
              active={this.state.selectedTab}
              onSelect={(name) => this.setState({ selectedTab: name })}
            />
          </ErrorBoundary>
          <ErrorBoundary>{content}</ErrorBoundary>
        </div>
      </div>
    );
  }
}

NewsletterTemplates.contextType = GlobalContext;

NewsletterTemplates.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string,
    }).isRequired,
  }).isRequired,
};

NewsletterTemplates.display = 'NewsletterTemplates';

export { NewsletterTemplates };
