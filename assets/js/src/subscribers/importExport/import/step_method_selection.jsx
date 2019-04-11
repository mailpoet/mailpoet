import React from 'react';
import PropTypes from 'prop-types';
import PreviousNextStepButtons from './previous_next_step_buttons.jsx';

const canGoNext = false;

function StepMethodSelection({
  navigate,
}) {
  return (
    <>
      <PreviousNextStepButtons
        canGoNext={canGoNext}
        hidePrevious
        onNextAction={() => navigate('step_data_manipulation', { trigger: true })}
      />
    </>
  );
}

StepMethodSelection.propTypes = {
  navigate: PropTypes.func.isRequired,
};

export default StepMethodSelection;
