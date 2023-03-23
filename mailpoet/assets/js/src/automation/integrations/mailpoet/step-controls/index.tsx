import { __ } from '@wordpress/i18n';
import { chartBar } from '@wordpress/icons';
import { Hooks } from 'wp-js-hooks';
import { MoreControlType, StepMoreControlsType } from '../../../types/filters';
import { Step } from '../../../editor/components/automation/types';

const emailStatisticsControl = (step: Step): MoreControlType => {
  const hasEmail = Number.isInteger(step.args?.email_id);
  return {
    key: 'statistics',
    control: {
      icon: chartBar,
      title: __('Email statistics', 'mailpoet'),
      isDisabled: !hasEmail,
      onClick: () => {
        window.open(
          `admin.php?page=mailpoet-newsletters#/stats/${
            step.args.email_id as number
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
    'mailpoet.automation.step.more-controls',
    'mailpoet',
    (controls: StepMoreControlsType, step: Step): StepMoreControlsType => {
      if (step.key === 'mailpoet:send-email') {
        return {
          statistics: emailStatisticsControl(step),
          ...controls,
        };
      }
      return controls;
    },
  );
}
