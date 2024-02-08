import { createRoot } from 'react-dom/client';
import { HashRouter, Redirect, Route, Switch } from 'react-router-dom';
import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { initStore as initSettingsStore } from 'settings/store';
import { useSetting } from 'settings/store/hooks';
import { WooCommerceController } from './woocommerce-controller';
import { registerTranslations, withBoundary } from '../common';
import { WelcomeWizardStepsController } from './welcome-wizard-controller';

function App(): JSX.Element {
  let basePath = '/steps/1';
  const [savedStep] = useSetting('welcome_wizard_current_step');
  if (typeof savedStep === 'string' && savedStep.startsWith('/steps')) {
    basePath = savedStep;
  }
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
  registerTranslations();
  initSettingsStore();
  const root = createRoot(container);
  root.render(<App />);
}
