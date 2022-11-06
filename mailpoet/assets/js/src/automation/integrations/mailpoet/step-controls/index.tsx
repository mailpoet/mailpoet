import { __ } from '@wordpress/i18n';
import { chartBar } from '@wordpress/icons';
import { Hooks } from 'wp-js-hooks';
import { MoreControlType, StepMoreControlsType } from '../../../types/filters';
import { StepType } from '../../../editor/store';
import { Step } from '../../../editor/components/workflow/types';

const emailStatisticsControl = (step: Step): MoreControlType => {
  const hasEmail = step.args?.email_id > 0;
  return {
    key: 'statistics',
    control: {
      icon: chartBar,
      title: __('Email statistics', 'mailpoet'),
      isDisabled: !hasEmail,
      onClick: () => {
        window.open(
          `admin.php?page=mailpoet-newsletters#/stats/${
            step.args.email_id as string
          }`,
          '_blank',
        );
      },
    },
    slot: () => null,
  };
};

export function registerStepControls() {
  Hooks.addFilter(
    'mailpoet.automation.workflow.step.more-controls',
    'mailpoet',
    (
      controls: StepMoreControlsType,
      step: Step,
      stepType: StepType,
    ): StepMoreControlsType => {
      if (stepType.key === 'mailpoet:send-email') {
        return {
          statistics: emailStatisticsControl(step),
          ...controls,
        };
      }
      return controls;
    },
  );
}
