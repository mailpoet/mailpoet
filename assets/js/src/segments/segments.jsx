import React from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route } from 'react-router-dom';

import MailPoet from 'mailpoet';
import RoutedTabs from 'common/tabs/routed_tabs';
import Tab from 'common/tabs/tab';
import SegmentList from 'segments/list.jsx';
import DynamicSegmentList from 'segments/dynamic_segments_list.jsx';
import SegmentForm from 'segments/form.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import DynamicSegmentForm from './dynamic_segments_form';

const container = document.getElementById('segments_container');

const Tabs = () => (
  <RoutedTabs activeKey="" routerType="switch-only">
    <Tab key="" route="*" title={MailPoet.I18n.t('pageTitle')}>
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
);

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
      <Notices />
      <Switch>
        <Route path="/new" component={SegmentForm} />
        <Route path="/edit/:id" component={SegmentForm} />
        <Route path="/new-segment" component={DynamicSegmentForm} />
        <Route path="/edit-segment/:id" component={DynamicSegmentForm} />
        <Route path="/segments/(.*)?" component={Tabs} />
        <Route path="*" component={Tabs} />
      </Switch>
    </HashRouter>
  </GlobalContext.Provider>
);

if (container) {
  ReactDOM.render(<App />, container);
}
