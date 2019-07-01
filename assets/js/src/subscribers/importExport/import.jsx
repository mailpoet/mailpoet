import React from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route, Redirect } from 'react-router-dom';

import StepMethodSelection from './import/step_method_selection.jsx';

const container = document.getElementById('import_container');

if (container) {
  ReactDOM.render((
    <HashRouter>
      <Switch>
        <Route path="/step_method_selection" component={StepMethodSelection} />
        <Route path="*" render={() => <Redirect to="/step_method_selection" />} />
      </Switch>
    </HashRouter>
  ), container);
}
