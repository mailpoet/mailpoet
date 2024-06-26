import { createRoot } from 'react-dom/client';
import { HashRouter, Route, Routes } from 'react-router-dom';

import { SegmentList } from 'segments/static/list';
import { SegmentForm } from 'segments/static/form';
import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, withBoundary } from 'common';

const container = document.getElementById('static_segments_container');
const FormWithBoundary = withBoundary(SegmentForm);
const ListWithBoundary = withBoundary(SegmentList);

function App(): JSX.Element {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <GlobalNotices />
        <Notices />
        <Routes>
          <Route path="/new" element={<FormWithBoundary />} />
          <Route path="/edit/:id" element={<FormWithBoundary />} />
          <Route path="*" element={<ListWithBoundary />} />
        </Routes>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(<App />);
}
