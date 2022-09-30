import { __ } from '@wordpress/i18n';
import { Workflow } from '../../workflow';

type Props = {
  workflow: Workflow;
};

export function Subscribers({ workflow }: Props): JSX.Element {
  return (
    <ul className="mailpoet-automation-stats">
      <li className="mailpoet-automation-stats-entered">
        {new Intl.NumberFormat().format(workflow.stats.totals.entered)}
        <span>{__('Entered', 'mailpoet')}</span>
      </li>
      <li className="mailpoet-automation-stats-in-process">
        {new Intl.NumberFormat().format(workflow.stats.totals.in_progress)}
        <span>{__('Processing', 'mailpoet')}</span>
      </li>
      <li className="mailpoet-automation-stats-exited">
        {new Intl.NumberFormat().format(workflow.stats.totals.exited)}
        <span>{__('Exited', 'mailpoet')}</span>
      </li>
    </ul>
  );
}
