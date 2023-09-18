import { check, Icon } from '@wordpress/icons';
import { FlowSeparator } from './flow-separator';
import { Step as StepData } from './types';

type Props = {
  stepData: StepData;
  index: number;
};

export function FlowEnding({ stepData, index }: Props): JSX.Element {
  return (
    <div className="mailpoet-automation-editor-step-wrapper">
      <FlowSeparator stepData={stepData} index={index} />
      <Icon
        className="mailpoet-automation-editor-automation-end"
        icon={check}
      />
    </div>
  );
}
