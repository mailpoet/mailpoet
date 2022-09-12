import ReactDOM from 'react-dom';
import { useCallback, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { Button } from '@wordpress/components';
import { workflowTemplates } from './config';
import { TemplateListItem } from './components/template-list-item';
import { initializeApi } from '../api';
import { PremiumModal } from '../../common/premium_modal';

function Templates(): JSX.Element {
  const [showModal, setShowModal] = useState(false);
  const onClickScratchButton = useCallback(() => {
    const fromScratchCallback = Hooks.applyFilters(
      'mailpoet.automation.templates.from_scratch_button',
      () => {
        setShowModal(true);
      },
    );
    fromScratchCallback();
  }, []);
  return (
    <>
      <h1>{__('Choose your automation template', 'mailpoet')}</h1>
      <Button onClick={() => onClickScratchButton()}>
        {__('From scratch test', 'mailpoet')}
      </Button>
      {showModal && (
        <PremiumModal
          onRequestClose={() => {
            setShowModal(false);
          }}
          tracking={{
            utm_medium: 'upsell_modal',
            utm_campaign: 'create_automation_from_scratch',
          }}
        >
          {__('You cannot create automation from scratch.', 'mailpoet')}
        </PremiumModal>
      )}
      <ul className="mailpoet-templates">
        {workflowTemplates.map((template) => (
          <TemplateListItem key={template.slug} template={template} />
        ))}
      </ul>
    </>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation_templates');
  if (!root) {
    return;
  }

  initializeApi();
  ReactDOM.render(<Templates />, root);
});
