import { ComponentProps, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { AutomationTemplate, automationTemplateCategories } from '../config';
import {
  PremiumModal,
  premiumValidAndActive,
} from '../../../common/premium-modal';
import { Item } from '../../../common/templates';
import { TemplateDetail } from './template-detail';

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

type Props = {
  template: AutomationTemplate;
};

export function TemplateListItem({ template }: Props): JSX.Element {
  const [showPremium, setShowPremium] = useState(false);
  const [showDetail, setShowDetail] = useState(false);

  return (
    <>
      <Item
        name={template.name}
        description={template.description}
        category={getCategory(template)}
        badge={getBadge(template)}
        disabled={template.type === 'coming-soon'}
        onClick={() => {
          if (template.type === 'premium' && !premiumValidAndActive) {
            setShowPremium(true);
          } else {
            setShowDetail(true);
          }
        }}
      />
      {showDetail && (
        <TemplateDetail
          template={template}
          onRequestClose={() => setShowDetail(false)}
        />
      )}
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
