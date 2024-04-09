import { ComponentProps } from 'react';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { AutomationItem } from '../../store/types';
import { MailPoet } from '../../../../mailpoet';

type Props = {
  automation: AutomationItem;
  label?: string;
  variant?: ComponentProps<typeof Button>['variant'];
};

export function Analytics({
  automation,
  label,
  variant = 'link',
}: Props): JSX.Element {
  const { id, isLegacy } = automation;
  return isLegacy ? (
    <Button
      variant={variant}
      href={`?page=mailpoet-newsletters&context=automation#/stats/${id}`}
    >
      {label ?? __('Analytics', 'mailpoet')}
    </Button>
  ) : (
    <Button
      variant={variant}
      href={addQueryArgs(MailPoet.urls.automationAnalytics, {
        id: automation.id,
      })}
    >
      {label ?? __('Analytics', 'mailpoet')}
    </Button>
  );
}
