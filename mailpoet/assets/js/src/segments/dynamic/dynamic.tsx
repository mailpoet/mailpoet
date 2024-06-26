import { createRoot } from 'react-dom/client';
import { HashRouter, Route, Routes, useLocation } from 'react-router-dom';

import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, withBoundary } from 'common';
import { Editor } from 'segments/dynamic/editor';
import { DynamicSegmentList } from 'segments/dynamic/list';
import { SegmentTemplates } from 'segments/dynamic/templates';
import * as ROUTES from 'segments/routes';
import { createStore, storeName } from 'segments/dynamic/store';
import { useEffect, useRef } from 'react';
import { useDispatch } from '@wordpress/data';

const container = document.getElementById('dynamic_segments_container');

function HistoryListener() {
  const { setPreviousPage } = useDispatch(storeName);
  const location = useLocation();

  const previousPageRef = useRef(location.pathname);

  useEffect(() => {
    void setPreviousPage(previousPageRef.current);
    previousPageRef.current = location.pathname;
  }, [location, setPreviousPage]);

  return null;
}

const EditorWithBoundary = withBoundary(Editor);
const TemplatesWithBoundary = withBoundary(SegmentTemplates);
const ListWithBoundary = withBoundary(DynamicSegmentList);

function App(): JSX.Element {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <HistoryListener />
        <GlobalNotices />
        <Notices />
        <Routes>
          <Route
            path={ROUTES.NEW_DYNAMIC_SEGMENT}
            element={<EditorWithBoundary />}
          />
          <Route
            path={`${ROUTES.EDIT_DYNAMIC_SEGMENT}/:id`}
            element={<EditorWithBoundary />}
          />
          <Route
            path={ROUTES.DYNAMIC_SEGMENT_TEMPLATES}
            element={<TemplatesWithBoundary />}
          />
          <Route path="*" element={<ListWithBoundary />} />
        </Routes>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

if (container) {
  registerTranslations();
  createStore();
  const root = createRoot(container);
  root.render(<App />);
}
