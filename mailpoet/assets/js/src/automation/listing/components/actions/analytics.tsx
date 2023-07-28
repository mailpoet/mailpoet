import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { Automation } from '../../automation';
import { MailPoet } from '../../../../mailpoet';

type Props = {
  automation: Automation;
  label?: string;
};

export function Analytics({ automation, label }: Props): JSX.Element {
  return (
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
