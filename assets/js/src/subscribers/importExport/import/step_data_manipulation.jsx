import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import PreviousNextStepButtons from './previous_next_step_buttons.jsx';
import Warnings from './step_data_manipulation/warnings.jsx';
import MatchTable from './step_data_manipulation/match_table.jsx';
import SelectSegment from './step_data_manipulation/select_segment.jsx';
import UpdateExistingSubscribers from './step_data_manipulation/update_existing_subscribers.jsx';
import doImport from './step_data_manipulation/do_import.jsx';

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
  setStepDataManipulationData,
}) {
  const [selectedSegments, setSelectedSegments] = useState([]);
  const [updateExistingSubscribers, setUpdateExistingSubscribers] = useState(true);
  useEffect(
    () => {
      if (typeof (stepMethodSelectionData) === 'undefined') {
        history.replace('step_method_selection');
      }
    },
    [stepMethodSelectionData, history],
  );

  const importSubscribers = () => {
    doImport(
      stepMethodSelectionData.subscribers,
      selectedSegments,
      updateExistingSubscribers,
      (importResults) => {
        setStepDataManipulationData(importResults);
        history.push('step_results');
      }
    );
  };

  if (typeof (stepMethodSelectionData) === 'undefined') {
    return null;
  }
  return (
    <div className="mailpoet_data_manipulation_step">
      <Warnings
        stepMethodSelectionData={stepMethodSelectionData}
      />
      <MatchTable
        subscribersCount={stepMethodSelectionData.subscribersCount}
        subscribers={stepMethodSelectionData.subscribers}
        header={stepMethodSelectionData.header}
      />
      <SelectSegment setSelectedSegments={setSelectedSegments} />
      <UpdateExistingSubscribers
        setUpdateExistingSubscribers={setUpdateExistingSubscribers}
        updateExistingSubscribers={updateExistingSubscribers}
      />
      <PreviousNextStepButtons
        canGoNext={selectedSegments.length > 0}
        onPreviousAction={() => (
          history.push(getPreviousStepLink(stepMethodSelectionData, subscribersLimitForValidation))
        )}
        onNextAction={importSubscribers}
      />
    </div>
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
  setStepDataManipulationData: PropTypes.func.isRequired,
};

StepDataManipulation.defaultProps = {
  stepMethodSelectionData: undefined,
};

export default withRouter(StepDataManipulation);
