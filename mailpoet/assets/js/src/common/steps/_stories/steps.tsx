import { useState } from 'react';
import Button from '../../button/button';
import Heading from '../../typography/heading/heading';
import Steps from '../steps';
import StepsContent from '../steps_content';

export default {
  title: 'Steps',
  component: Steps,
};

export function StepsWithoutTitles() {
  const [step, setStep] = useState(1);

  const nextStep = () => setStep(step + 1);
  const previousStep = () => setStep(step - 1);

  return (
    <>
      <Steps count={5} current={step} />
      <StepsContent>
        <Heading level={3}>{`Step ${step}`}</Heading>
        <p>
          Lorem ipsum dolor sit amet consectetur adipisicing elit. Soluta natus
          consequuntur saepe harum nesciunt eum, a nulla facilis architecto
          incidunt odio voluptas praesentium, ipsa laboriosam animi! Officiis
          atque odio nulla.
        </p>
        <div>
          <Button
            onClick={previousStep}
            dimension="small"
            variant="secondary"
            isDisabled={step === 1}
          >
            Previous step
          </Button>
          <Button onClick={nextStep} dimension="small" isDisabled={step === 5}>
            Next step
          </Button>
        </div>
      </StepsContent>
    </>
  );
}

export function StepsWithTitles() {
  const [step, setStep] = useState(1);

  const nextStep = () => setStep(step + 1);
  const previousStep = () => setStep(step - 1);

  return (
    <>
      <Steps
        count={5}
        current={step}
        titles={['First', 'Second', 'Third', 'Fourth', 'Fifth']}
      />
      <StepsContent>
        <Heading level={3}>{`Step ${step}`}</Heading>
        <p>
          Lorem ipsum dolor sit amet consectetur adipisicing elit. Soluta natus
          consequuntur saepe harum nesciunt eum, a nulla facilis architecto
          incidunt odio voluptas praesentium, ipsa laboriosam animi! Officiis
          atque odio nulla.
        </p>
        <div>
          <Button
            onClick={previousStep}
            dimension="small"
            variant="secondary"
            isDisabled={step === 1}
          >
            Previous step
          </Button>
          <Button onClick={nextStep} dimension="small" isDisabled={step === 5}>
            Next step
          </Button>
        </div>
      </StepsContent>
    </>
  );
}
