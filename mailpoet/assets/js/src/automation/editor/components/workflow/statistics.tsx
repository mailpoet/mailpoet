import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../store';

export function Statistics(): JSX.Element {
  const { workflow } = useSelect(
    (select) => ({
      workflow: select(storeName).getWorkflowData(),
    }),
    [],
  );

  return (
    <div>
      <ul className="mailpoet-automation-stats">
        <li className="mailpoet-automation-stats-item">
          <span className="mailpoet-automation-stats-label">
            {__('Total Entered', 'mailpoet')}
          </span>
          {new Intl.NumberFormat().format(workflow.stats.totals.entered)}
        </li>
        <li className="mailpoet-automation-stats-item">
          <span className="mailpoet-automation-stats-label">
            {__('Total Processing', 'mailpoet')}
          </span>
          {new Intl.NumberFormat().format(workflow.stats.totals.in_progress)}
        </li>
        <li className="mailpoet-automation-stats-item">
          <span className="mailpoet-automation-stats-label">
            {__('Total Exited', 'mailpoet')}
          </span>
          {new Intl.NumberFormat().format(workflow.stats.totals.exited)}
        </li>
      </ul>
    </div>
  );
}
