import { Hooks } from 'wp-js-hooks';
import { Step as StepData } from '../../../../../../../editor/components/automation/types';
import { StepFooter } from '../step-footer';
import { SendEmailPanel } from '../steps/send-email';
import { StatisticSeparator } from '../statistic-separator';
import { moreControls } from './more-controls';

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
      return function statisticSeperatorWrapper(
        previousStepData: StepData,
        index: number,
      ) {
        return (
          <>
            {previousStepData.next_steps.length > 1 && (
              <div
                className={
                  index < previousStepData.next_steps.length / 2
                    ? 'mailpoet-automation-editor-separator-curve-leaf-left'
                    : 'mailpoet-automation-editor-separator-curve-leaf-right'
                }
              />
            )}
            <StatisticSeparator previousStepId={previousStepData.id} />
          </>
        );
      };
    },
    20,
  );
}
