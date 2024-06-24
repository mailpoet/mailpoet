import { __ } from '@wordpress/i18n';
import { AutomationEndedIcon, AutomationInProgressIcon } from './icons';

export function Footer({ runStatus }: { runStatus: string }) {
  if (runStatus === 'running') {
    return (
      <div className="mailpoet-analytics-activity-modal-footer">
        <AutomationInProgressIcon />
        <span>{__('Automation in progress', 'mailpoet')}</span>
      </div>
    );
  }
  return (
    <div className="mailpoet-analytics-activity-modal-footer">
      <AutomationEndedIcon />
      <span>{__('Automation ended', 'mailpoet')}</span>
    </div>
  );
}
