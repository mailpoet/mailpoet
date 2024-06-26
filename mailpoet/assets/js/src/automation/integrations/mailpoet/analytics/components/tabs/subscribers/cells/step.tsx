import { useMemo } from 'react';
import { useSelect } from '@wordpress/data';
import { storeName as editorStoreName } from '../../../../../../../editor/store';
import { ColoredIcon } from '../../../../../../../editor/components/icons';
import { Step } from '../../../../../../../editor/components/automation/types';
import { LockedBadge } from '../../../../../../../../common/premium-modal/locked-badge';

export function StepCell({
  name,
  data,
}: {
  name: string;
  data?: Step;
}): JSX.Element {
  const { stepType } = useSelect((s) => ({
    stepType: data.key ? s(editorStoreName).getStepType(data.key) : undefined,
  }));

  const info = useMemo(() => {
    const subtitle = stepType ? stepType.subtitle(data, 'other') : '';
    if (typeof subtitle === 'object' && subtitle.type === LockedBadge) {
      return undefined;
    }
    if (data?.key === 'mailpoet:send-email' && subtitle === name) {
      return data?.args?.subject as string | undefined;
    }
    return subtitle;
  }, [data, name, stepType]);

  return (
    <div className="mailpoet-analytics-subscribers-step-cell">
      {stepType ? (
        <ColoredIcon
          width="16px"
          height="16px"
          background={stepType.background}
          foreground={stepType.foreground}
          icon={stepType.icon}
        />
      ) : (
        <div />
      )}
      <div>
        <p>{name}</p>
        {info && <span>{info}</span>}
      </div>
    </div>
  );
}
