import { MSSStepFirstPart } from './pitch_mss_step/first_part';
import { MSSStepSecondPart } from './pitch_mss_step/second_part';

type WelcomeWizardPitchMSSStepPropType = {
  subscribersCount: number;
  next: () => void;
  finishWizard: (redirect_url?: string) => void;
};

function WelcomeWizardPitchMSSStep({
  subscribersCount,
  next,
  finishWizard,
}: WelcomeWizardPitchMSSStepPropType): JSX.Element {
  const part = 'first';

  if (part === 'first') {
    return (
      <MSSStepFirstPart
        subscribersCount={subscribersCount}
        next={next}
        finishWizard={finishWizard}
      />
    );
  }

  return <MSSStepSecondPart />;
}

export { WelcomeWizardPitchMSSStep };
