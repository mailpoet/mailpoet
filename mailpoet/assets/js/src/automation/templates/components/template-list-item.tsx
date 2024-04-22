import { ComponentProps, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { AutomationTemplate, automationTemplateCategories } from '../config';
import {
  PremiumModal,
  premiumValidAndActive,
} from '../../../common/premium-modal';
import { Item } from '../../../common/templates';
import { MailPoet } from '../../../mailpoet';

type Badge = ComponentProps<typeof Item>['badge'];

const getCategory = (template: AutomationTemplate): string =>
  automationTemplateCategories.find(({ slug }) => slug === template.category)
    ?.name ?? __('Uncategorized', 'mailpoet');

const isLocked = (template: AutomationTemplate): boolean => {
  const maxSteps = Number(MailPoet.capabilities?.automationSteps?.value ?? 0);
  const steps = Number(template.required_capabilities?.automationSteps ?? 0);

  const isPremium = template.type === 'premium';
  if (isPremium && !premiumValidAndActive) {
    return true;
  }

  if (maxSteps === 0 || steps === 0) {
    return false;
  }

  // the Thank loyal customers template is available from the Basic plan
  if (MailPoet.tier === 0 && isPremium) {
    return true;
  }

  return steps > maxSteps;
};

const getBadge = (template: AutomationTemplate): Badge => {
  if (template.type === 'coming-soon') {
    return 'coming-soon';
  }
  if (isLocked(template)) {
    return 'premium';
  }
  return undefined;
};

type Props = {
  template: AutomationTemplate;
  onSelect: () => void;
};

export function TemplateListItem({ template, onSelect }: Props): JSX.Element {
  const [showPremium, setShowPremium] = useState(false);

  return (
    <>
      <Item
        name={template.name}
        description={template.description}
        category={getCategory(template)}
        badge={getBadge(template)}
        disabled={template.type === 'coming-soon'}
        onClick={() => {
          if (isLocked(template)) {
            setShowPremium(true);
          } else {
            onSelect();
          }
        }}
      />
      {showPremium && (
        <PremiumModal
          onRequestClose={() => {
            setShowPremium(false);
          }}
          data={{ capabilities: template.required_capabilities }}
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
