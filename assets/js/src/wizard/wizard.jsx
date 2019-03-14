import React from 'react';
import ReactDOM from 'react-dom';
import { Route, HashRouter, Redirect } from 'react-router-dom';
import WelcomeWizardStepsController from './welcome_wizard_controller.jsx';

const container = document.getElementById('mailpoet_wizard_container');

if (container) {
  ReactDOM.render((
    <HashRouter>
      <div>
        <Route exact path="/" render={() => <Redirect to="/steps/1" />} />
        <Route path="/steps/:step" component={WelcomeWizardStepsController} />
      </div>
    </HashRouter>
  ), container);
}
