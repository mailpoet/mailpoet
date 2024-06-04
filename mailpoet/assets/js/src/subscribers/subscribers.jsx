import { createRoot } from 'react-dom/client';
import { HashRouter, Route, Switch } from 'react-router-dom';

import { SubscriberList } from 'subscribers/list.tsx';
import { SubscriberForm } from 'subscribers/form.jsx';
import { SubscriberStats } from 'subscribers/stats';
import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, withBoundary } from 'common';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <GlobalNotices />
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
  const root = createRoot(container);
  root.render(<App />);
}
