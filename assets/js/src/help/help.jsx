import React from 'react';
import ReactDOM from 'react-dom';
import { Router, Route, IndexRedirect, useRouterHistory } from 'react-router';
import { createHashHistory } from 'history';

import SystemStatus from 'help/system_status.jsx';
import SystemInfo from 'help/system_info.jsx';
import KnowledgeBase from 'help/knowledge_base.jsx';

const history = useRouterHistory(createHashHistory)({ queryKey: false });

const App = React.createClass({
  render() {
    return this.props.children;
  },
});

const container = document.getElementById('help_container');

if (container) {
  ReactDOM.render((
    <Router history={history}>
      <Route path="/" component={App}>
        <IndexRedirect to="systemStatus" />
        {/* Pages */}
        <Route path="systemStatus(/)**" params={{ tab: 'systemStatus' }} component={SystemStatus} />
        <Route path="systemInfo(/)**" params={{ tab: 'systemInfo' }} component={SystemInfo} />
        <Route path="knowledgeBase(/)**" params={{ tab: 'knowledgeBase' }} component={KnowledgeBase} />
      </Route>
    </Router>
  ), container);
}
