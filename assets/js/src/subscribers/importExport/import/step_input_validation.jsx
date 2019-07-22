import React, { useEffect } from 'react';
import { withRouter } from 'react-router-dom';
import PropTypes from 'prop-types';
import PreviousNextStepButtons from './previous_next_step_buttons.jsx';

function StepInputValidation({ stepMethodSelectionData, history }) {
  useEffect(
    () => {
      if (typeof (stepMethodSelectionData) === 'undefined') {
        history.replace('step_method_selection');
      }
    },
    [stepMethodSelectionData, history],
  );

  function isFormValid() {
    return false;
  }
  return (
    <div className="mailpoet_import_validation_step">
      <PreviousNextStepButtons
        canGoNext={isFormValid()}
        onPreviousAction={() => history.push('step_method_selection')}
        onNextAction={() => history.push('step_data_manipulation')}
      />
    </div>
  );
}

StepInputValidation.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
    replace: PropTypes.func.isRequired,
  }).isRequired,
  stepMethodSelectionData: PropTypes.shape({
    duplicate: PropTypes.arrayOf(PropTypes.string),
    header: PropTypes.arrayOf(PropTypes.string),
    invalid: PropTypes.arrayOf(PropTypes.string),
    role: PropTypes.arrayOf(PropTypes.string),
    subscribersCount: PropTypes.number,
    subscribers: PropTypes.arrayOf(PropTypes.arrayOf(PropTypes.string)),
  }),
};

StepInputValidation.defaultProps = {
  stepMethodSelectionData: undefined,
};

export default withRouter(StepInputValidation);
