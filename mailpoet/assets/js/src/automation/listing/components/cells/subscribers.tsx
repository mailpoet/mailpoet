import { __ } from '@wordpress/i18n';
import { Workflow } from '../../workflow';

type Props = {
  workflow: Workflow;
};

export function Subscribers({ workflow }: Props): JSX.Element {
  return (
    <ul className="mailpoet-automation-stats">
      <li className="mailpoet-automation-stats-item">
        {new Intl.NumberFormat().format(workflow.stats.totals.entered)}
        <span className="mailpoet-automation-stats-label">
          {__('Entered', 'mailpoet')}
        </span>
      </li>
      <li className="mailpoet-automation-stats-item">
        {new Intl.NumberFormat().format(workflow.stats.totals.in_progress)}
        <span className="mailpoet-automation-stats-label">
          {__('Processing', 'mailpoet')}
        </span>
      </li>
      <li className="mailpoet-automation-stats-item">
        {new Intl.NumberFormat().format(workflow.stats.totals.exited)}
        <span className="mailpoet-automation-stats-label">
          {__('Exited', 'mailpoet')}
        </span>
      </li>
    </ul>
  );
}
