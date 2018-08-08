import React from 'react';
import ReactDOM from 'react-dom';
import { Router, Route, IndexRedirect, useRouterHistory } from 'react-router';
import { createHashHistory } from 'history';
import WelcomeWizardStepsController from './steps_controller.jsx';

const container = document.getElementById('welcome_wizard_container');

if (container) {
  const history = useRouterHistory(createHashHistory)({ queryKey: false });

  ReactDOM.render((
    <Router history={history}>
      <Route path={'/'}>
        <IndexRedirect to={'steps/1'} />
        <Route
          path={'steps/:step'}
          component={WelcomeWizardStepsController}
        />
      </Route>
    </Router>
  ), container);
}
