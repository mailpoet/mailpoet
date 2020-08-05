import React from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route } from 'react-router-dom';

import SubscriberList from 'subscribers/list.jsx';
import SubscriberForm from 'subscribers/form.jsx';
import { SubscriberStats } from 'subscribers/stats';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
      <Notices />
      <Switch>
        <Route path="/new" component={SubscriberForm} />
        <Route path="/edit/:id" component={SubscriberForm} />
        <Route path="/stats/:id" component={SubscriberStats} />
        <Route path="*" component={SubscriberList} />
      </Switch>
    </HashRouter>
  </GlobalContext.Provider>
);

const container = document.getElementById('subscribers_container');

if (container) {
  ReactDOM.render(<App />, container);
}
