import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { AutomationItem } from '../../store/types';
import { MailPoet } from '../../../../mailpoet';

type Props = {
  automation: AutomationItem;
  label?: string;
};

export function Analytics({ automation, label }: Props): JSX.Element {
  const { id, isLegacy } = automation;
  return isLegacy ? (
    <a href={`?page=mailpoet-newsletters#/stats/${id}`}>
      {label ?? __('Analytics', 'mailpoet')}
    </a>
  ) : (
    <Button
      variant="link"
      href={addQueryArgs(MailPoet.urls.automationAnalytics, {
        id: automation.id,
      })}
    >
      {label ?? __('Analytics', 'mailpoet')}
    </Button>
  );
}
