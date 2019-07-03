import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import PreviousNextStepButtons from './previous_next_step_buttons.jsx';
import Warnings from './step_data_manipulation/warnings.jsx';
import MatchTable from './step_data_manipulation/match_table.jsx';

function getPreviousStepLink(importData, subscribersLimitForValidation) {
  if (importData === undefined) {
    return 'step_method_selection';
  }
  if (importData.subscribersCount === undefined) {
    return 'step_method_selection';
  }
  if (importData.subscribersCount < subscribersLimitForValidation) {
    return 'step_method_selection';
  }
  return 'step_input_validation';
}

function StepDataManipulation({
  history,
  stepMethodSelectionData,
  subscribersLimitForValidation,
}) {
  useEffect(
    () => {
      if (typeof (stepMethodSelectionData) === 'undefined') {
        history.replace('step_method_selection');
      }
    },
    [stepMethodSelectionData],
  );

  if (typeof (stepMethodSelectionData) === 'undefined') {
    return null;
  }
  return (
    <>
      <Warnings
        stepMethodSelectionData={stepMethodSelectionData}
      />
      <div className="inside">
        <MatchTable
          subscribersCount={stepMethodSelectionData.subscribersCount}
          subscribers={stepMethodSelectionData.subscribers}
          header={stepMethodSelectionData.header}
        />
      </div>
      <PreviousNextStepButtons
        canGoNext={false}
        onPreviousAction={() => (
          history.push(getPreviousStepLink(stepMethodSelectionData, subscribersLimitForValidation))
        )}
        onNextAction={() => history.push('todo')}
      />
    </>
  );
}

StepDataManipulation.propTypes = {
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
  subscribersLimitForValidation: PropTypes.number.isRequired,
};

StepDataManipulation.defaultProps = {
  stepMethodSelectionData: undefined,
};

export default withRouter(StepDataManipulation);
