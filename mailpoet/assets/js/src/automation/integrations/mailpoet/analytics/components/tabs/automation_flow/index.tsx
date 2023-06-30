import { Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { Automation } from '../../../../../../editor/components/automation';
import { StatisticSeparator } from './statistic_separator';
import { Step as StepData } from '../../../../../../editor/components/automation/types';
import { storeName } from '../../../store';
import { AutomationPlaceholder } from './automation_placeholder';
import { StepFooter } from './step_footer';
import { SendEmailPanel } from './steps/send_email';

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

export function AutomationFlow(): JSX.Element {
  const { section } = useSelect(
    (s) => ({
      section: s(storeName).getSection('automation_flow'),
    }),
    [],
  );

  const isLoading = section.data === undefined;

  if (isLoading) {
    return <AutomationPlaceholder />;
  }

  return (
    <>
      {section.data.tree_is_inconsistent && (
        <div className="mailpoet-automation-editor-automation-notices">
          <Notice
            status="warning"
            isDismissible={false}
            className="mailpoet-automation-flow-notice"
          >
            <p>
              {__(
                'In this time period, the automation structure did change and therefore some numbers in the flow chart might not be accurate.',
                'mailpoet',
              )}
            </p>
          </Notice>
        </div>
      )}
      <Automation context="view" />
    </>
  );
}
