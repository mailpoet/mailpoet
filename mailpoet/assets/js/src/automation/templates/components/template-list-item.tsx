import { ComponentProps, useCallback, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { AutomationTemplate, automationTemplateCategories } from '../config';
import { MailPoet } from '../../../mailpoet';
import { Notice } from '../../../notices/notice';
import {
  PremiumModal,
  premiumValidAndActive,
} from '../../../common/premium-modal';
import { Item } from '../../../common/templates';

type Badge = ComponentProps<typeof Item>['badge'];

const getCategory = (template: AutomationTemplate): string =>
  automationTemplateCategories.find(({ slug }) => slug === template.category)
    ?.name ?? __('Uncategorized', 'mailpoet');

const getBadge = (template: AutomationTemplate): Badge => {
  if (template.type === 'coming-soon') {
    return 'coming-soon';
  }
  if (template.type === 'premium') {
    return 'premium';
  }
  return undefined;
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

type Props = {
  template: AutomationTemplate;
};

export function TemplateListItem({ template }: Props): JSX.Element {
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

  return (
    <>
      {notice}
      <Item
        name={template.name}
        description={template.description}
        category={getCategory(template)}
        badge={getBadge(template)}
        disabled={template.type === 'coming-soon'}
        isBusy={loading}
        onClick={() => {
          if (template.type === 'premium' && !premiumValidAndActive) {
            setShowPremium(true);
            return;
          }
          void createAutomationFromTemplate(template.slug);
        }}
      />
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
    </>
  );
}
