
export const getStepsCount = () => {
  let stepsCount = 4;
  if (window.is_woocommerce_active) {
    stepsCount += 1;
  }
  return stepsCount;
};

export const redirectToNextStep = (history, finishWizard, currentStep) => {
  const stepsCount = getStepsCount();
  if (currentStep < stepsCount) {
    history.push(`/steps/${currentStep + 1}`);
  } else {
    finishWizard();
  }
};
