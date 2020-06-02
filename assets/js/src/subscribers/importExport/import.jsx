/* eslint-disable react/jsx-props-no-spreading */
import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import {
  HashRouter, Switch, Route, Redirect,
} from 'react-router-dom';
import ScrollToTop from 'common/scroll_to_top.jsx';

import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import StepMethodSelection from './import/step_method_selection.jsx';
import StepInputValidation from './import/step_input_validation.jsx';
import StepDataManipulation from './import/step_data_manipulation.jsx';
import StepResults from './import/step_results.jsx';
import StepOfferClearout from './import/step_offer_clearout';

const container = document.getElementById('import_container');

const subscribersLimitForValidation = 100;

const ImportSubscribers = () => {
  const [stepMethodSelectionData, setStepMethodSelectionData] = useState(undefined);
  const [stepDataManipulationData, setStepDataManipulationData] = useState({});
  const contextValue = useGlobalContextValue(window);
  return (
    <GlobalContext.Provider value={contextValue}>
      <HashRouter>
        <Notices />
        <ScrollToTop>
          <Switch>
            <Route
              path="/step_offer_clearout"
              render={(props) => (
                <StepOfferClearout {...props} />
              )}
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
            <Route
              path="*"
              render={() => <Redirect to="/step_offer_clearout" />}
            />
          </Switch>
        </ScrollToTop>
      </HashRouter>
    </GlobalContext.Provider>
  );
};

if (container) {
  ReactDOM.render(<ImportSubscribers />, container);
}
