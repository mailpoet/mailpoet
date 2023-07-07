import ReactDOM from 'react-dom';
import { HashRouter, Route, Switch } from 'react-router-dom';

import { SubscriberList } from 'subscribers/list.tsx';
import { SubscriberForm } from 'subscribers/form.jsx';
import { SubscriberStats } from 'subscribers/stats';
import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, withBoundary } from 'common';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <Notices />
        <Switch>
          <Route path="/new" render={withBoundary(SubscriberForm)} />
          <Route path="/edit/:id" render={withBoundary(SubscriberForm)} />
          <Route
            path="/stats/:id/(.*)?"
            component={withBoundary(SubscriberStats)}
          />
          <Route path="*" component={withBoundary(SubscriberList)} />
        </Switch>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('subscribers_container');

if (container) {
  registerTranslations();
  ReactDOM.render(<App />, container);
}
