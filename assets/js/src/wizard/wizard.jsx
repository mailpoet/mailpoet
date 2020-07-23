import React from 'react';
import ReactDOM from 'react-dom';
import {
  Route,
  HashRouter,
  Redirect,
  Switch,
} from 'react-router-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import WelcomeWizardStepsController from './welcome_wizard_controller.jsx';
import WooCommerceController from './woocommerce_controller.jsx';

const App = () => {
  let basePath = '/steps/1';
  if (window.location.search.includes('woocommerce-setup')) {
    basePath = '/woocommerce';
  }
  const contextValue = useGlobalContextValue(window);
  return (
    <GlobalContext.Provider value={contextValue}>
      <HashRouter>
        <Notices />
        <Switch>
          <Route path="/steps/:step" component={WelcomeWizardStepsController} />
          <Route path="/woocommerce" component={WooCommerceController} />
          <Route render={() => <Redirect to={basePath} />} />
        </Switch>
      </HashRouter>
    </GlobalContext.Provider>
  );
};

const container = document.getElementById('mailpoet-wizard-container');

if (container) {
  ReactDOM.render(<App />, container);
}
