import { createRoot } from 'react-dom/client';
import { __ } from '@wordpress/i18n';
import { registerTranslations } from 'common';
import { automationTemplateCategories, automationTemplates } from './config';
import { initializeApi } from '../api';
import { TopBarWithBeamer } from '../../common/top-bar/top-bar';
import { FromScratchButton } from './components/from-scratch';
import { BackButton, PageHeader } from '../../common/page-header';
import { MailPoet } from '../../mailpoet';
import { Footer, TabPanel, TabTitle } from '../../common/templates';
import { TemplatesGrid } from './components/templates-grid';

const tabs = [
  {
    name: 'all',
    title: (
      <TabTitle
        title={__('All', 'mailpoet')}
        count={automationTemplates.length}
      />
    ),
  },
  ...automationTemplateCategories
    .map((category) => ({
      ...category,
      count: automationTemplates.filter(
        (template) => template.category === category.slug,
      ).length,
    }))
    .filter(({ count }) => count > 0)
    .map(({ name, slug, count }) => ({
      name: slug,
      title: <TabTitle title={name} count={count} />,
    })),
];

function Templates(): JSX.Element {
  if (window.location.search.includes('loadedvia=woo_multichannel_dashboard')) {
    window.MailPoet.trackEvent(
      'MailPoet - WooCommerce Multichannel Marketing dashboard > Automation template selection page',
      {
        'WooCommerce version': window.mailpoet_woocommerce_version,
      },
    );
  }

  return (
    <div className="mailpoet-main-container">
      <TopBarWithBeamer />
      <PageHeader
        heading={__('Start with a template', 'mailpoet')}
        headingPrefix={
          <BackButton
            href={MailPoet.urls.automationListing}
            label={__('Back to automation list', 'mailpoet')}
          />
        }
      >
        <FromScratchButton />
      </PageHeader>

      <TabPanel tabs={tabs}>
        {(tab) => (
          <TemplatesGrid
            templates={automationTemplates.filter(
              (template) =>
                tab.name === 'all' || template.category === tab.name,
            )}
          />
        )}
      </TabPanel>

      <Footer>
        <p>{__('Can’t find what you’re looking for?', 'mailpoet')}</p>
        <FromScratchButton variant="link" />
      </Footer>
    </div>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('mailpoet_automation_templates');
  if (!container) {
    return;
  }

  registerTranslations();
  initializeApi();
  const root = createRoot(container);
  root.render(<Templates />);
});
