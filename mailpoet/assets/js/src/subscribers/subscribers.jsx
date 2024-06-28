import { createRoot } from 'react-dom/client';
import { HashRouter, Route, Routes } from 'react-router-dom';

import { SubscriberList } from 'subscribers/list.tsx';
import { SubscriberForm } from 'subscribers/form.jsx';
import { SubscriberStats } from 'subscribers/stats';
import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, ErrorBoundary } from 'common';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <GlobalNotices />
        <Notices />
        <Routes>
          <Route
            path="/new"
            element={
              <ErrorBoundary>
                <SubscriberForm />
              </ErrorBoundary>
            }
          />
          <Route
            path="/edit/:id"
            element={
              <ErrorBoundary>
                <SubscriberForm />
              </ErrorBoundary>
            }
          />
          <Route
            path="/stats/:id/*"
            element={
              <ErrorBoundary>
                <SubscriberStats />
              </ErrorBoundary>
            }
          />
          <Route
            path="*"
            element={
              <ErrorBoundary>
                <SubscriberList />
              </ErrorBoundary>
            }
          />
        </Routes>
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
