import React from 'react';
import ReactDOM from 'react-dom';
import { Route, HashRouter, Redirect } from 'react-router-dom';
import WelcomeWizardStepsController from './welcome_wizard_controller.jsx';
import WooCommerceImportController from './woocommerce_import_controller.jsx';
import RevenueTrackingPermissionController from './revenue_tracking_permission.jsx';

const container = document.getElementById('mailpoet_wizard_container');

if (container) {
  let basePath = '/steps/1';
  if (window.location.search.includes('revenue-tracking-permission')) {
    basePath = '/revenue-tracking-permission';
  } else if (window.location.search.includes('woocommerce-list-import')) {
    basePath = '/import';
  }
  ReactDOM.render((
    <HashRouter>
      <div>
        <Route exact path="/" render={() => <Redirect to={basePath} />} />
        <Route path="/steps/:step" component={WelcomeWizardStepsController} />
        <Route path="/import" component={WooCommerceImportController} />
        <Route path="/revenue-tracking-permission" component={RevenueTrackingPermissionController} />
      </div>
    </HashRouter>
  ), container);
}
