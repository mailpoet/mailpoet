import ReactDOM from 'react-dom';
import { HashRouter, Redirect, Route, Switch } from 'react-router-dom';
import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { initStore as initSettingsStore } from 'settings/store';
import { WooCommerceController } from './woocommerce_controller';
import { withBoundary } from '../common';
import { WelcomeWizardStepsController } from './welcome_wizard_controller';

function App(): JSX.Element {
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
          <Route
            path="/steps/:step"
            component={withBoundary(WelcomeWizardStepsController)}
          />
          <Route
            path="/woocommerce"
            component={withBoundary(WooCommerceController)}
          />
          <Route render={() => <Redirect to={basePath} />} />
        </Switch>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('mailpoet-wizard-container');

if (container) {
  initSettingsStore();
  ReactDOM.render(<App />, container);
}
