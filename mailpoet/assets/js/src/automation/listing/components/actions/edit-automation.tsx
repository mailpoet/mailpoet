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

export function EditAutomation({
  automation,
  label,
  variant = 'link',
}: Props): JSX.Element {
  const { id, isLegacy } = automation;
  return isLegacy ? (
    <Button
      variant={variant}
      href={`?page=mailpoet-newsletter-editor&id=${id}`}
    >
      {label ?? __('Edit', 'mailpoet')}
    </Button>
  ) : (
    <Button
      variant={variant}
      href={addQueryArgs(MailPoet.urls.automationEditor, { id: automation.id })}
    >
      {label ?? __('Edit', 'mailpoet')}
    </Button>
  );
}
