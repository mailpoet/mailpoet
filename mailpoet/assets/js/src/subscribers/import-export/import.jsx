import { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { HashRouter, Navigate, Route, Routes } from 'react-router-dom';
import { ScrollToTop } from 'common/scroll-to-top.jsx';

import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, ErrorBoundary } from 'common';
import { StepMethodSelection } from './import/step-method-selection.jsx';
import { StepInputValidation } from './import/step-input-validation';
import { StepDataManipulation } from './import/step-data-manipulation.jsx';
import { StepResults } from './import/step-results.jsx';
import { StepCleanList } from './import/step-clean-list';

const container = document.getElementById('import_container');

const subscribersLimitForValidation = 100;

function ImportSubscribers() {
  const [stepMethodSelectionData, setStepMethodSelectionData] =
    useState(undefined);
  const [stepDataManipulationData, setStepDataManipulationData] = useState({});
  const contextValue = useGlobalContextValue(window);
  return (
    <GlobalContext.Provider value={contextValue}>
      <HashRouter>
        <Notices />
        <ScrollToTop>
          <Routes>
            <Route
              path="/step_clean_list"
              element={
                <ErrorBoundary>
                  <StepCleanList />
                </ErrorBoundary>
              }
            />
            <Route
              path="/step_method_selection"
              element={
                <StepMethodSelection
                  setStepMethodSelectionData={setStepMethodSelectionData}
                  subscribersLimitForValidation={subscribersLimitForValidation}
                />
              }
            />
            <Route
              path="/step_input_validation"
              element={
                <StepInputValidation
                  stepMethodSelectionData={stepMethodSelectionData}
                />
              }
            />
            <Route
              path="/step_data_manipulation"
              element={
                <StepDataManipulation
                  stepMethodSelectionData={stepMethodSelectionData}
                  subscribersLimitForValidation={subscribersLimitForValidation}
                  setStepDataManipulationData={setStepDataManipulationData}
                />
              }
            />
            <Route
              path="/step_results"
              element={
                <StepResults
                  errors={stepDataManipulationData.errors}
                  createdSubscribers={stepDataManipulationData.created}
                  updatedSubscribers={stepDataManipulationData.updated}
                  segments={stepDataManipulationData.segments}
                  addedToSegmentWithWelcomeNotification={
                    stepDataManipulationData.added_to_segment_with_welcome_notification
                  }
                />
              }
            />
            <Route path="*" element={<Navigate to="/step_clean_list" />} />
          </Routes>
        </ScrollToTop>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(<ImportSubscribers />);
}
