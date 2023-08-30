import { __ } from '@wordpress/i18n';
import { Automation, AutomationStatus } from '../../automation';

type Props = {
  automation: Automation;
};

export function Status({ automation }: Props): JSX.Element {
  let status = '';
  switch (automation.status) {
    case AutomationStatus.ACTIVE:
      status = __('Active', 'mailpoet');
      break;
    case AutomationStatus.DEACTIVATING:
      status = __('Deactivating', 'mailpoet');
      break;
    default:
      status = __('Draft', 'mailpoet');
  }

  return (
    <div className="mailpoet-automation-listing-cell-status">{status}</div>
  );
}
