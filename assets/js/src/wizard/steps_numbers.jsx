
export const getStepsCount = () => {
  let stepsCount = 3;
  if (window.is_woocommerce_active) {
    stepsCount += 1;
  }
  return stepsCount;
};

export const redirectToNextStep = (history, finishWizard, currentStep) => {
  if (currentStep === 1) {
    history.push('/steps/2');
    return;
  }
  if (currentStep === 2) {
    history.push('/steps/3');
    return;
  }
  const stepsCount = getStepsCount();
  if (currentStep === 3 && stepsCount > 3) {
    history.push('/steps/4');
    return;
  }
  if (currentStep === 3 && stepsCount === 3) {
    finishWizard();
    return;
  }
  if (currentStep === 4) {
    finishWizard();
  }
};
