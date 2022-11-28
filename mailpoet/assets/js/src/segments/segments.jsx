import ReactDOM from 'react-dom';
import { HashRouter, Redirect, Route, Switch } from 'react-router-dom';

import { MailPoet } from 'mailpoet';
import { RoutedTabs } from 'common/tabs/routed_tabs';
import { Tab } from 'common/tabs/tab';
import { SegmentList } from 'segments/list.jsx';
import { SegmentForm } from 'segments/form.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { Notices } from 'notices/notices.jsx';
import { withBoundary } from 'common';
import { Editor } from './dynamic/editor';
import { DynamicSegmentList } from './dynamic/list.jsx';
import { ListHeading } from './heading';

const container = document.getElementById('segments_container');

function Tabs() {
  return (
    <>
      <ListHeading />
      <RoutedTabs activeKey="lists" routerType="switch-only">
        <Tab
          key="lists"
          route="lists/(.*)?"
          title={MailPoet.I18n.t('pageTitle')}
        >
          <SegmentList />
        </Tab>
        <Tab
          key="segments"
          route="segments/(.*)?"
          title={MailPoet.I18n.t('pageTitleSegments')}
          automationId="dynamic-segments-tab"
        >
          <DynamicSegmentList />
        </Tab>
      </RoutedTabs>
    </>
  );
}

Tabs.displayName = 'SegmentTabs';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <Notices />
        <Switch>
          <Route exact path="/" render={() => <Redirect to="/lists" />} />
          <Route path="/new" render={withBoundary(SegmentForm)} />
          <Route path="/edit/:id" render={withBoundary(SegmentForm)} />
          <Route path="/new-segment" render={withBoundary(Editor)} />
          <Route path="/edit-segment/:id" render={withBoundary(Editor)} />
          <Route path="/segments/(.*)?" render={withBoundary(Tabs)} />
          <Route path="/lists/(.*)?" render={withBoundary(Tabs)} />
        </Switch>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

if (container) {
  ReactDOM.render(<App />, container);
}
