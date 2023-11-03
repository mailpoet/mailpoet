import { createRoot } from 'react-dom/client';
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import { registerTranslations } from 'common';
import { automationTemplates } from './config';
import { TemplateListItem } from './components/template-list-item';
import { initializeApi } from '../api';
import { TopBarWithBeamer } from '../../common/top-bar/top-bar';
import {
  FromScratchButton,
  FromScratchListItem,
} from './components/from-scratch';

function Templates(): JSX.Element {
  return (
    <>
      <TopBarWithBeamer />
      <Flex className="mailpoet-automation-templates-heading">
        <h1 className="wp-heading-inline">
          {__('Choose your automation template', 'mailpoet')}
        </h1>
        <FromScratchButton />
      </Flex>

      <ul className="mailpoet-automation-templates">
        {automationTemplates.map((template) => (
          <TemplateListItem key={template.slug} template={template} />
        ))}
        <FromScratchListItem />
      </ul>
    </>
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
