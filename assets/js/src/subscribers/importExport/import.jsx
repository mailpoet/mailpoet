import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route, Redirect } from 'react-router-dom';

import StepMethodSelection from './import/step_method_selection.jsx';

const container = document.getElementById('import_container');

const ImportSubscribers = () => {
  const [stepMethodSelection, setStepMethodSelection] = useState(undefined);
  return (
    <HashRouter>
      <Switch>
        <Route path="/step_method_selection" render={props => <StepMethodSelection {...props} setStepMethodSelection={setStepMethodSelection} />} />
        <Route path="*" render={() => <Redirect to="/step_method_selection" />} />
      </Switch>
    </HashRouter>
  );
};

if (container) {
  ReactDOM.render(<ImportSubscribers />, container);
}
