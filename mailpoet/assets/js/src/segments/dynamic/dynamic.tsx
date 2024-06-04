import { createRoot } from 'react-dom/client';
import { HashRouter, Route, Switch, useHistory } from 'react-router-dom';

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
  const history = useHistory();

  const previousPageRef = useRef(history.location.pathname);

  useEffect(
    () =>
      history.listen((location) => {
        void setPreviousPage(previousPageRef.current);

        previousPageRef.current = location.pathname;
      }),
    [history, setPreviousPage],
  );

  return null;
}

function App(): JSX.Element {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <HistoryListener />
        <GlobalNotices />
        <Notices />
        <Switch>
          <Route
            path={ROUTES.NEW_DYNAMIC_SEGMENT}
            component={withBoundary(Editor)}
          />
          <Route
            path={`${ROUTES.EDIT_DYNAMIC_SEGMENT}/:id`}
            component={withBoundary(Editor)}
          />
          <Route
            path={ROUTES.DYNAMIC_SEGMENT_TEMPLATES}
            component={withBoundary(SegmentTemplates)}
          />
          <Route path="*" component={withBoundary(DynamicSegmentList)} />
        </Switch>
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
