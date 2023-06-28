import { Hooks } from 'wp-js-hooks';
import { Automation } from '../../../../../../editor/components/automation';
import { StatisticSeparator } from './statistic_separator';
import { Step as StepData } from '../../../../../../editor/components/automation/types';

Hooks.addFilter(
  'mailpoet.automation.render_step_separator',
  'mailpoet',
  () =>
    function statisticSeperatorWrapper(previousStepData: StepData) {
      return <StatisticSeparator previousStepId={previousStepData.id} />;
    },
);

export function AutomationFlow(): JSX.Element {
  return <Automation context="view" />;
}
