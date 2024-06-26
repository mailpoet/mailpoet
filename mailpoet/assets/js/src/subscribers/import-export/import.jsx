import { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { HashRouter, Redirect, Route, Switch } from 'react-router-dom';
import { ScrollToTop } from 'common/scroll-to-top.jsx';

import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, withBoundary } from 'common';
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
          <Switch>
            <Route path="/step_clean_list">{withBoundary(StepCleanList)}</Route>
            <Route path="/step_method_selection">
              {(props) => (
                <StepMethodSelection
                  {...props}
                  setStepMethodSelectionData={setStepMethodSelectionData}
                  subscribersLimitForValidation={subscribersLimitForValidation}
                />
              )}
            </Route>
            <Route path="/step_input_validation">
              {(props) => (
                <StepInputValidation
                  {...props}
                  stepMethodSelectionData={stepMethodSelectionData}
                />
              )}
            </Route>
            <Route path="/step_data_manipulation">
              {(props) => (
                <StepDataManipulation
                  {...props}
                  stepMethodSelectionData={stepMethodSelectionData}
                  subscribersLimitForValidation={subscribersLimitForValidation}
                  setStepDataManipulationData={setStepDataManipulationData}
                />
              )}
            </Route>
            <Route path="/step_results">
              {(props) => (
                <StepResults
                  {...props}
                  errors={stepDataManipulationData.errors}
                  createdSubscribers={stepDataManipulationData.created}
                  updatedSubscribers={stepDataManipulationData.updated}
                  segments={stepDataManipulationData.segments}
                  addedToSegmentWithWelcomeNotification={
                    stepDataManipulationData.added_to_segment_with_welcome_notification
                  }
                />
              )}
            </Route>
            <Route path="*">{() => <Redirect to="/step_clean_list" />}</Route>
          </Switch>
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
