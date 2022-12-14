import ReactDOM from 'react-dom';
import { HashRouter, Redirect, Route, Switch } from 'react-router-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { Notices } from 'notices/notices.jsx';
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
            render={withBoundary(WelcomeWizardStepsController)}
          />
          <Route
            path="/woocommerce"
            render={withBoundary(WooCommerceController)}
          />
          <Route render={() => <Redirect to={basePath} />} />
        </Switch>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('mailpoet-wizard-container');

if (container) {
  ReactDOM.render(<App />, container);
}
