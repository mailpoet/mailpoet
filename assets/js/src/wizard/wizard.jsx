import React from 'react';
import ReactDOM from 'react-dom';
import { Route, HashRouter, Redirect } from 'react-router-dom';
import WelcomeWizardStepsController from './welcome_wizard_controller.jsx';
import WooCommerceImportController from './woocommerce_import_controller.jsx';

const container = document.getElementById('mailpoet_wizard_container');

if (container) {
  const basePath = window.location.search.includes('woocommerce-list-import') ? '/import' : '/steps/1';
  ReactDOM.render((
    <HashRouter>
      <div>
        <Route exact path="/" render={() => <Redirect to={basePath} />} />
        <Route path="/steps/:step" component={WelcomeWizardStepsController} />
        <Route path="/import" component={WooCommerceImportController} />
      </div>
    </HashRouter>
  ), container);
}
