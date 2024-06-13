import { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { PreviousNextStepButtons } from './previous-next-step-buttons.jsx';
import { Warnings } from './step-data-manipulation/warnings.jsx';
import { MatchTable } from './step-data-manipulation/match-table.jsx';
import { SelectSegment } from './step-data-manipulation/select-segment.jsx';
import { NewSubscribersStatus } from './step-data-manipulation/new-subscribers-status';
import { ExistingSubscribersStatus } from './step-data-manipulation/existing-subscribers-status';
import { UpdateExistingSubscribers } from './step-data-manipulation/update-existing-subscribers.jsx';
import { doImport } from './step-data-manipulation/do-import.jsx';
import { AssignTags } from './step-data-manipulation/assign-tags';

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
  subscribersLimitForValidation,
  setStepDataManipulationData,
  stepMethodSelectionData = undefined,
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

export const StepDataManipulation = withRouter(StepDataManipulationComponent);
