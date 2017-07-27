import React from 'react';
import ReactDOM from 'react-dom';
import { Router, Route, IndexRedirect, useRouterHistory } from 'react-router';
import { createHashHistory } from 'history';

import KnowledgeBase from 'help/knowledge_base.jsx';
import SystemInfo from 'help/system_info.jsx';

const history = useRouterHistory(createHashHistory)({ queryKey: false });

const App = React.createClass({
  render() {
    return this.props.children;
  },
});

const container = document.getElementById('help_container');

if(container) {

  ReactDOM.render((
    <Router history={ history }>
      <Route path="/" component={ App }>
        <IndexRedirect to="knowledgeBase" />
        {/* Pages */}
        <Route path="knowledgeBase(/)**" params={{ tab: 'knowledgeBase' }} component={ KnowledgeBase } />
        <Route path="systemInfo(/)**" params={{ tab: 'systemInfo' }} component={ SystemInfo } />
      </Route>
    </Router>
  ), container);

}
