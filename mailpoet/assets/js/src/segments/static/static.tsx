import { createRoot } from 'react-dom/client';
import { HashRouter, Route, Switch } from 'react-router-dom';

import { SegmentList } from 'segments/static/list';
import { SegmentForm } from 'segments/static/form';
import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices.jsx';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, withBoundary } from 'common';

const container = document.getElementById('static_segments_container');

function App(): JSX.Element {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <GlobalNotices />
        <Notices />
        <Switch>
          <Route path="/new" component={withBoundary(SegmentForm)} />
          <Route path="/edit/:id" component={withBoundary(SegmentForm)} />
          <Route path="*" component={withBoundary(SegmentList)} />
        </Switch>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(<App />);
}
