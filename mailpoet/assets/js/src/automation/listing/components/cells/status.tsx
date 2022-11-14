import { __ } from '@wordpress/i18n';
import { Automation, AutomationStatus } from '../../automation';

type Props = {
  automation: Automation;
};

export function Status({ automation }: Props): JSX.Element {
  return (
    <div className="mailpoet-automation-listing-cell-status">
      {automation.status === AutomationStatus.ACTIVE
        ? __('Active', 'mailpoet')
        : __('Draft', 'mailpoet')}
    </div>
  );
}
