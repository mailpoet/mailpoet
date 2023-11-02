import { createRoot } from 'react-dom/client';
import { __ } from '@wordpress/i18n';
import { registerTranslations } from 'common';
import { automationTemplates } from './config';
import { TemplateListItem } from './components/template-list-item';
import { initializeApi } from '../api';
import { TopBarWithBeamer } from '../../common/top-bar/top-bar';
import {
  FromScratchButton,
  FromScratchListItem,
} from './components/from-scratch';
import { PageHeader } from '../../common/page-header';

function Templates(): JSX.Element {
  return (
    <div className="mailpoet-main-container">
      <TopBarWithBeamer />
      <PageHeader heading={__('Start with a template', 'mailpoet')}>
        <FromScratchButton />
      </PageHeader>

      <ul className="mailpoet-automation-templates">
        {automationTemplates.map((template) => (
          <TemplateListItem key={template.slug} template={template} />
        ))}
        <FromScratchListItem />
      </ul>
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
