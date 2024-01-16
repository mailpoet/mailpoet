import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { AutomationItem } from '../../store/types';
import { MailPoet } from '../../../../mailpoet';

type Props = {
  automation: AutomationItem;
  label?: string;
};

export function EditAutomation({ automation, label }: Props): JSX.Element {
  const { id, isLegacy } = automation;
  return isLegacy ? (
    <Button variant="link" href={`?page=mailpoet-newsletter-editor&id=${id}`}>
      {label ?? __('Edit', 'mailpoet')}
    </Button>
  ) : (
    <Button
      variant="link"
      href={addQueryArgs(MailPoet.urls.automationEditor, { id: automation.id })}
    >
      {label ?? __('Edit', 'mailpoet')}
    </Button>
  );
}
