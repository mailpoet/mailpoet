import { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { PreviousNextStepButtons } from './previous_next_step_buttons.jsx';
import { Warnings } from './step_data_manipulation/warnings.jsx';
import { MatchTable } from './step_data_manipulation/match_table.jsx';
import { SelectSegment } from './step_data_manipulation/select_segment.jsx';
import { NewSubscribersStatus } from './step_data_manipulation/new_subscribers_status';
import { ExistingSubscribersStatus } from './step_data_manipulation/existing_subscribers_status';
import { UpdateExistingSubscribers } from './step_data_manipulation/update_existing_subscribers.jsx';
import { doImport } from './step_data_manipulation/do_import.jsx';
import { AssignTags } from './step_data_manipulation/assign_tags';

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

function StepDataManipulationComponent({
  history,
  stepMethodSelectionData,
  subscribersLimitForValidation,
  setStepDataManipulationData,
}) {
  const [selectedSegments, setSelectedSegments] = useState([]);
  const [updateExistingSubscribers, setUpdateExistingSubscribers] =
    useState(true);
  const [newSubscribersStatus, setNewSubscribersStatus] =
    useState('subscribed');
  const [existingSubscribersStatus, setExistingSubscribersStatus] =
    useState('dontUpdate');
  const [selectedTags, setSelectedTags] = useState([]);
  useEffect(() => {
    if (typeof stepMethodSelectionData === 'undefined') {
      history.replace('step_method_selection');
    }
  }, [stepMethodSelectionData, history]);

  const importSubscribers = () => {
    doImport(
      stepMethodSelectionData.subscribers,
      selectedSegments,
      newSubscribersStatus,
      existingSubscribersStatus,
      updateExistingSubscribers,
      selectedTags,
      (importResults) => {
        setStepDataManipulationData(importResults);
        history.push('step_results');
      },
    );
  };

  if (typeof stepMethodSelectionData === 'undefined') {
    return null;
  }
  return (
    <div data-automation-id="import_data_manipulation_step">
      <Warnings stepMethodSelectionData={stepMethodSelectionData} />
      <MatchTable
        subscribersCount={stepMethodSelectionData.subscribersCount}
        subscribers={stepMethodSelectionData.subscribers}
        header={stepMethodSelectionData.header}
      />
      <div className="mailpoet-settings-grid">
        <SelectSegment setSelectedSegments={setSelectedSegments} />
        <NewSubscribersStatus
          newSubscribersStatus={newSubscribersStatus}
          setNewSubscribersStatus={setNewSubscribersStatus}
        />
        <ExistingSubscribersStatus
          existingSubscribersStatus={existingSubscribersStatus}
          setExistingSubscribersStatus={setExistingSubscribersStatus}
        />
        <UpdateExistingSubscribers
          setUpdateExistingSubscribers={setUpdateExistingSubscribers}
          updateExistingSubscribers={updateExistingSubscribers}
        />
        <AssignTags
          selectedTags={selectedTags}
          setSelectedTags={setSelectedTags}
        />
        <PreviousNextStepButtons
          canGoNext={selectedSegments.length > 0}
          onPreviousAction={() =>
            history.push(
              getPreviousStepLink(
                stepMethodSelectionData,
                subscribersLimitForValidation,
              ),
            )
          }
          onNextAction={importSubscribers}
          isLastStep
        />
      </div>
    </div>
  );
}

StepDataManipulationComponent.propTypes = {
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
    subscribers: PropTypes.arrayOf(
      // all subscribers
      PropTypes.arrayOf(
        // single subscribers
        PropTypes.oneOfType(
          // properties of a subscriber
          [PropTypes.string, PropTypes.number],
        ),
      ),
    ),
  }),
  subscribersLimitForValidation: PropTypes.number.isRequired,
  setStepDataManipulationData: PropTypes.func.isRequired,
};

StepDataManipulationComponent.defaultProps = {
  stepMethodSelectionData: undefined,
};

export const StepDataManipulation = withRouter(StepDataManipulationComponent);
