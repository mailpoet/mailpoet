import React from 'react';
import ReactDOM from 'react-dom';
import {
  HashRouter, Route, Redirect, Switch,
} from 'react-router-dom';

import KnowledgeBase from 'help/knowledge_base.jsx';
import SystemInfo from 'help/system_info.jsx';
import SystemStatus from 'help/system_status.jsx';
import YourPrivacy from 'help/your_privacy.jsx';

const container = document.getElementById('help_container');

if (container) {
  ReactDOM.render((
    <HashRouter>
      <Switch>
        <Route exact path="/" render={() => <Redirect to="/knowledgeBase" />} />
        <Route path="/knowledgeBase" component={KnowledgeBase} />
        <Route path="/systemStatus" component={SystemStatus} />
        <Route path="/systemInfo" component={SystemInfo} />
        <Route path="/yourPrivacy" component={YourPrivacy} />
      </Switch>
    </HashRouter>
  ), container);
}
