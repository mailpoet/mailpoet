import { useState } from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Redirect, Route, Switch } from 'react-router-dom';
import { ScrollToTop } from 'common/scroll_to_top.jsx';

import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { Notices } from 'notices/notices.jsx';
import { withBoundary } from 'common';
import { StepMethodSelection } from './import/step_method_selection.jsx';
import { StepInputValidation } from './import/step_input_validation.jsx';
import { StepDataManipulation } from './import/step_data_manipulation.jsx';
import { StepResults } from './import/step_results.jsx';
import { StepCleanList } from './import/step_clean_list';

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
            <Route
              path="/step_clean_list"
              render={withBoundary(StepCleanList)}
            />
            <Route
              path="/step_method_selection"
              render={(props) => (
                <StepMethodSelection
                  {...props}
                  setStepMethodSelectionData={setStepMethodSelectionData}
                  subscribersLimitForValidation={subscribersLimitForValidation}
                />
              )}
            />
            <Route
              path="/step_input_validation"
              render={(props) => (
                <StepInputValidation
                  {...props}
                  stepMethodSelectionData={stepMethodSelectionData}
                />
              )}
            />
            <Route
              path="/step_data_manipulation"
              render={(props) => (
                <StepDataManipulation
                  {...props}
                  stepMethodSelectionData={stepMethodSelectionData}
                  subscribersLimitForValidation={subscribersLimitForValidation}
                  setStepDataManipulationData={setStepDataManipulationData}
                />
              )}
            />
            <Route
              path="/step_results"
              render={(props) => (
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
            />
            <Route path="*" render={() => <Redirect to="/step_clean_list" />} />
          </Switch>
        </ScrollToTop>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

if (container) {
  ReactDOM.render(<ImportSubscribers />, container);
}
