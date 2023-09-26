import { _x } from '@wordpress/i18n';
import { check, Icon } from '@wordpress/icons';
import { Statistics as BaseStatistics } from '../../../../../../components/statistics';

const statisticItems = [
  {
    key: 'entered',
    // translators: Total number of subscribers who entered an automation
    label: _x('Total Entered', 'automation stats', 'mailpoet'),
    value: 0,
  },
  {
    key: 'processing',
    // translators: Total number of subscribers who are being processed in an automation
    label: _x('Total Processing', 'automation stats', 'mailpoet'),
    value: 0,
  },
  {
    key: 'exited',
    // translators: Total number of subscribers who exited an automation, no matter the result
    label: _x('Total Exited', 'automation stats', 'mailpoet'),
    value: 0,
  },
];

function StepPlaceholder(): JSX.Element {
  return (
    <>
      <div className="mailpoet-automation-editor-step-wrapper mailpoet-automation-editor-step-wrapper-placeholder">
        <div className="mailpoet-automation-editor-step">
          <div className="mailpoet-automation-editor-step-icon" />
          <div className="mailpoet-automation-editor-step-content">
            <div className="mailpoet-automation-editor-step-title" />
            <div className="mailpoet-automation-editor-step-subtitle" />
          </div>
          <div className="mailpoet-automation-editor-step-footer" />
        </div>
      </div>
      <div className="mailpoet-automation-editor-separator" />
    </>
  );
}

export function AutomationPlaceholder(): JSX.Element {
  return (
    <div className="mailpoet-automation-editor-automation-wrapper">
      <div className="mailpoet-automation-editor-stats mailpoet-automation-editor-stats-placeholder">
        <BaseStatistics items={statisticItems} />
      </div>
      <StepPlaceholder />
      <StepPlaceholder />
      <StepPlaceholder />
      <Icon
        className="mailpoet-automation-editor-automation-end"
        icon={check}
      />
      <div />
    </div>
  );
}
