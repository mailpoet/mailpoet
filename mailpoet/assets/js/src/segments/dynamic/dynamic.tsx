import ReactDOM from 'react-dom';
import { HashRouter, Route, Switch } from 'react-router-dom';

import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, withBoundary } from 'common';
import { Editor } from 'segments/dynamic/editor';
import { DynamicSegmentList } from 'segments/dynamic/list';
import { SegmentTemplates } from 'segments/dynamic/templates';

const container = document.getElementById('dynamic_segments_container');

function App(): JSX.Element {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <Notices />
        <Switch>
          <Route path="/new-segment" component={withBoundary(Editor)} />
          <Route path="/edit-segment/:id" component={withBoundary(Editor)} />
          <Route
            path="/segment-templates"
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
  ReactDOM.render(<App />, container);
}
