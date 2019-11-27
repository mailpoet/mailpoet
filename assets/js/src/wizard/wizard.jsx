import React from 'react';
import ReactDOM from 'react-dom';
import {
  Route,
  HashRouter,
  Redirect,
  Switch,
} from 'react-router-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import WelcomeWizardStepsController from './welcome_wizard_controller.jsx';
import WooCommerceImportController from './woocommerce_import_controller.jsx';
import RevenueTrackingPermissionController from './revenue_tracking_permission.jsx';

const App = () => {
  let basePath = '/steps/1';
  if (window.location.search.includes('revenue-tracking-permission')) {
    basePath = '/revenue-tracking-permission';
  } else if (window.location.search.includes('woocommerce-list-import')) {
    basePath = '/import';
  }
  const contextValue = useGlobalContextValue(window);
  return (
    <GlobalContext.Provider value={contextValue}>
      <HashRouter>
        <Switch>
          <Route path="/steps/:step" component={WelcomeWizardStepsController} />
          <Route path="/import" component={WooCommerceImportController} />
          <Route path="/revenue-tracking-permission" component={RevenueTrackingPermissionController} />
          <Route render={() => <Redirect to={basePath} />} />
        </Switch>
      </HashRouter>
    </GlobalContext.Provider>
  );
};

const container = document.getElementById('mailpoet_wizard_container');

if (container) {
  ReactDOM.render(<App />, container);
}
