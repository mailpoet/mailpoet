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
          <Route path="/new">{withBoundary(SubscriberForm)}</Route>
          <Route path="/edit/:id">{withBoundary(SubscriberForm)}</Route>
          <Route path="/stats/:id/(.*)?">{withBoundary(SubscriberStats)}</Route>
          <Route path="*">{withBoundary(SubscriberList)}</Route>
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
