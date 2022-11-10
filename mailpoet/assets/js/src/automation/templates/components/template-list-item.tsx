import { useCallback, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { AutomationTemplate } from '../config';
import { MailPoet } from '../../../mailpoet';
import { Notice } from '../../../notices/notice';
import {
  PremiumModal,
  premiumValidAndActive,
} from '../../../common/premium_modal';

type TemplateListItemProps = {
  template: AutomationTemplate;
  heading?: 'h2' | 'h3';
};

const useCreateFromTemplate = () => {
  const [state, setState] = useState({
    data: undefined,
    loading: false,
    error: undefined,
  });

  const create = useCallback(async (slug: string) => {
    setState((prevState) => ({ ...prevState, loading: true }));
    try {
      const data = await apiFetch({
        path: `/automations/create-from-template`,
        method: 'POST',
        data: { slug },
      });
      setState((prevState) => ({ ...prevState, data }));
    } catch (error) {
      setState((prevState) => ({ ...prevState, error }));
    } finally {
      setState((prevState) => ({ ...prevState, loading: false }));
    }
  }, []);

  return [create, state] as const;
};

export function TemplateListItem({
  template,
  heading,
}: TemplateListItemProps): JSX.Element {
  const [showPremium, setShowPremium] = useState(false);
  const [createAutomationFromTemplate, { loading, error, data }] =
    useCreateFromTemplate();

  if (!error && data) {
    MailPoet.trackEvent('Automations > Template selected', {
      'Automation slug': template.slug,
    });
    window.location.href = addQueryArgs(MailPoet.urls.automationEditor, {
      id: data.data.id,
    });
  }

  let notice = null;
  if (error) {
    notice = (
      <Notice type="error" closable timeout={false}>
        <p>
          {error.data
            ? error.data.message
            : __('Could not create automation.', 'mailpoet')}
        </p>
      </Notice>
    );
  }

  const headingTag = heading ?? 'h2';
  return (
    <li
      className={`mailpoet-automation-template-list-item mailpoet-automation-template-list-item-${template.type}`}
    >
      {notice}
      <Button
        isBusy={loading}
        disabled={template.type === 'coming-soon'}
        onClick={() => {
          if (template.type === 'premium' && !premiumValidAndActive) {
            setShowPremium(true);
            return;
          }
          void createAutomationFromTemplate(template.slug);
        }}
      >
        <div className="badge">
          {template.type === 'coming-soon' && (
            <span>{__('Coming soon', 'mailpoet')}</span>
          )}
          {template.type === 'premium' && (
            <span>{__('Premium', 'mailpoet')}</span>
          )}
        </div>
        {headingTag === 'h3' && <h3>{template.name}&nbsp;→</h3>}
        {headingTag === 'h2' && <h2>{template.name}&nbsp;→</h2>}

        <p>{template.description}</p>
      </Button>
      {showPremium && (
        <PremiumModal
          onRequestClose={() => {
            setShowPremium(false);
          }}
          tracking={{
            utm_medium: 'upsell_modal',
            utm_campaign: 'automation_premium_template',
          }}
        >
          {__(
            'All templates and fully configurable automations are available in the premium version.',
            'mailpoet',
          )}
        </PremiumModal>
      )}
    </li>
  );
}
