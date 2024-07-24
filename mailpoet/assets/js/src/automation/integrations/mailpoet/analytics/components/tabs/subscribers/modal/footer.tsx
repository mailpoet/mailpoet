import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { AutomationEndedIcon, AutomationInProgressIcon } from './icons';
import { Run } from '../../../../store';

export function Footer({ run }: { run: Run }) {
  if (run.is_past_due) {
    const automationRunFilterValue = encodeURIComponent(
      `"automation_run_id":${run.id}`,
    );
    const pastDueActionsUrl = `/wp-admin/tools.php?page=action-scheduler&status=past-due&s=${automationRunFilterValue}`;
    return (
      <div className="mailpoet-analytics-activity-modal-footer">
        <AutomationInProgressIcon />
        <span>
          {createInterpolateElement(
            __(
              'Automation stuck. Run <link>past due actions</link> manually.',
              'mailpoet',
            ),
            {
              link: (
                // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
                <a
                  href={pastDueActionsUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                />
              ),
            },
          )}
        </span>
      </div>
    );
  }
  if (run.status === 'running') {
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
