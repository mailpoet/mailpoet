import ReactDOM from 'react-dom';
import { HashRouter, Redirect, Route, Switch } from 'react-router-dom';

import { MailPoet } from 'mailpoet';
import { RoutedTabs } from 'common/tabs/routed_tabs';
import { Tab } from 'common/tabs/tab';
import { SegmentList } from 'segments/list';
import { SegmentForm } from 'segments/form';
import { ListHeading } from 'segments/heading';
import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, withBoundary } from 'common';
import { Editor } from './dynamic/editor';
import { DynamicSegmentList } from './dynamic/list';

const container = document.getElementById('segments_container');

function Tabs(): JSX.Element {
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

function App(): JSX.Element {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <Notices />
        <Switch>
          <Route exact path="/" render={() => <Redirect to="/lists" />} />
          <Route path="/new" component={withBoundary(SegmentForm)} />
          <Route path="/edit/:id" component={withBoundary(SegmentForm)} />
          <Route path="/new-segment" component={withBoundary(Editor)} />
          <Route path="/edit-segment/:id" component={withBoundary(Editor)} />
          <Route path="/segments/(.*)?" component={withBoundary(Tabs)} />
          <Route path="/lists/(.*)?" component={withBoundary(Tabs)} />
        </Switch>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

if (container) {
  registerTranslations();
  ReactDOM.render(<App />, container);
}
