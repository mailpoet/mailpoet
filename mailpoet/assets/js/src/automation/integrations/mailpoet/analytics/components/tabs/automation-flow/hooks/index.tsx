import { Hooks } from 'wp-js-hooks';
import { Step as StepData } from '../../../../../../../editor/components/automation/types';
import { StepFooter } from '../step_footer';
import { SendEmailPanel } from '../steps/send_email';
import { StatisticSeparator } from '../statistic_separator';
import { moreControls } from './more_controls';

export function initHooks() {
  Hooks.addFilter(
    'mailpoet.automation.step.footer',
    'mailpoet',
    (element: JSX.Element | null, step: StepData, context: string) => {
      if (context !== 'view') {
        return element;
      }
      return <StepFooter step={step} />;
    },
  );

  Hooks.addFilter(
    'mailpoet.automation.step.more',
    'mailpoet',
    (element: JSX.Element | null, step: StepData, context: string) => {
      if (context !== 'view') {
        return element;
      }

      if (step.key === 'mailpoet:send-email') {
        return <SendEmailPanel step={step} />;
      }

      return element;
    },
  );

  Hooks.addFilter(
    'mailpoet.automation.step.more-controls',
    'mailpoet',
    moreControls,
    20,
  );

  Hooks.addFilter(
    'mailpoet.automation.render_step_separator',
    'mailpoet',
    (filterValue: () => JSX.Element, context) => {
      if (context !== 'view') {
        return filterValue;
      }
      return function statisticSeperatorWrapper(previousStepData: StepData) {
        return <StatisticSeparator previousStepId={previousStepData.id} />;
      };
    },
    20,
  );
}
