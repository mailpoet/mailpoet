import { useSelect } from '@wordpress/data';
import { _n, __, sprintf } from '@wordpress/i18n';
import { storeName as editorStoreName } from '../../../../../../../editor/store';
import { ColoredIcon } from '../../../../../../../editor/components/icons';
import { Step } from '../../../../../../../editor/components/automation/types';

function SendEmailData({
  name,
  stepData,
}: {
  name: string;
  stepData: Step;
}): null | JSX.Element {
  if (stepData?.args?.name && stepData.args.name !== name) {
    return <>{stepData.args.name}</>;
  }
  if (stepData?.args?.subject) {
    return <>{stepData.args.subject}</>;
  }
  return null;
}

function DelayData({ stepData }: { stepData: Step }): null | JSX.Element {
  if (!stepData?.args?.delay || !stepData?.args?.delay_type) {
    return null;
  }

  const map: Record<string, string> = {
    MINUTES: _n(
      'Wait %d minute',
      'Wait %d minutes',
      stepData.args.delay as number,
      'mailpoet',
    ),
    HOURS: _n(
      'Wait %d hour',
      'Wait %d hours',
      stepData.args.delay as number,
      'mailpoet',
    ),
    DAYS: _n(
      'Wait %d day',
      'Wait %d days',
      stepData.args.delay as number,
      'mailpoet',
    ),
    WEEKS: _n(
      'Wait %d week',
      'Wait %d weeks',
      stepData.args.delay as number,
      'mailpoet',
    ),
  };

  const type: string = stepData.args.delay_type as string;

  if (!map[type]) {
    return <>{sprintf(__('Wait %d', 'mailpoet'), stepData.args.delay)}</>;
  }
  return <>{sprintf(map[type], stepData.args.delay)}</>;
}

export function StepCell({
  step,
}: {
  step: { id: string; name: string };
}): JSX.Element {
  const { automation, steps } = useSelect((s) => ({
    automation: s(editorStoreName).getAutomationData(),
    steps: Object.values(s(editorStoreName).getSteps()),
  }));
  const stepData = Object.values(automation?.steps).find(
    (s) => s.id === step.id,
  );
  if (!stepData) {
    return (
      <div className="mailpoet-analytics-subscribers-step-cell">
        <div />
        <p>{step.name}</p>
      </div>
    );
  }
  const stepType = steps.find((s) => s.key === stepData.key);

  return (
    <div className="mailpoet-analytics-subscribers-step-cell">
      <ColoredIcon
        width="14px"
        height="14px"
        background={stepType.background}
        foreground={stepType.foreground}
        icon={stepType.icon}
      />
      <p>{step.name}</p>
      <span>
        {stepData?.key === 'mailpoet:send-email' && (
          <SendEmailData name={step.name} stepData={stepData} />
        )}
        {stepData?.key === 'core:delay' && <DelayData stepData={stepData} />}
      </span>
    </div>
  );
}
