import { useState } from 'react';
import { MSSStepFirstPart } from './pitch_mss_step/first_part';
import { MSSStepSecondPart } from './pitch_mss_step/second_part';
import { MSSStepThirdPart } from './pitch_mss_step/third_part';

type WelcomeWizardPitchMSSStepPropType = {
  subscribersCount: number;
  finishWizard: (redirect_url?: string) => void;
};

function WelcomeWizardPitchMSSStep({
  subscribersCount,
  finishWizard,
}: WelcomeWizardPitchMSSStepPropType): JSX.Element {
  const [stepPart, setStepPart] = useState('first');

  switch (stepPart) {
    case 'first':
      return (
        <MSSStepFirstPart
          subscribersCount={subscribersCount}
          finishWizard={finishWizard}
          setStepPart={setStepPart}
        />
      );
    case 'second':
      return <MSSStepSecondPart setStepPart={setStepPart} />;
    default:
      return <MSSStepThirdPart finishWizard={finishWizard} />;
  }
}

export { WelcomeWizardPitchMSSStep };
