import { useSelect } from '@wordpress/data';
import { Hooks } from 'wp-js-hooks';
import { Automation } from '../../../../../../editor/components/automation';
import { StatisticSeparator } from './statistic_separator';
import { Step as StepData } from '../../../../../../editor/components/automation/types';
import { storeName } from '../../../store';
import { AutomationPlaceholder } from './automation_placeholder';

Hooks.addFilter(
  'mailpoet.automation.render_step_separator',
  'mailpoet',
  () =>
    function statisticSeperatorWrapper(previousStepData: StepData) {
      return <StatisticSeparator previousStepId={previousStepData.id} />;
    },
  20,
);

export function AutomationFlow(): JSX.Element {
  const { section } = useSelect(
    (s) => ({
      section: s(storeName).getSection('automation_flow'),
    }),
    [],
  );

  const isLoading = section.data === undefined;

  return !isLoading ? <Automation context="view" /> : <AutomationPlaceholder />;
}
