import { createRoot } from 'react-dom/client';
import { HashRouter, Route, Routes } from 'react-router-dom';
import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, ErrorBoundary } from 'common';
import { FormList } from './list.jsx';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <GlobalNotices />
        <Notices />
        <MssAccessNotices />
        <Routes>
          <Route
            path="*"
            element={
              <ErrorBoundary>
                <FormList />
              </ErrorBoundary>
            }
          />
        </Routes>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('forms_container');

if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(<App />);
}
