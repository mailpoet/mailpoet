import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route, Redirect } from 'react-router-dom';

import MailPoet from 'mailpoet';
import RoutedTabs from 'common/tabs/routed_tabs';
import Tab from 'common/tabs/tab';
import SegmentList from 'segments/list.jsx';
import SegmentForm from 'segments/form.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import Editor from './dynamic/editor';
import DynamicSegmentList from './dynamic/list.jsx';
import ListHeading from './heading';

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

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <Notices />
        <Switch>
          <Route exact path="/" render={() => <Redirect to="/lists" />} />
          <Route path="/new" component={SegmentForm} />
          <Route path="/edit/:id" component={SegmentForm} />
          <Route path="/new-segment" component={Editor} />
          <Route path="/edit-segment/:id" component={Editor} />
          <Route path="/segments/(.*)?" component={Tabs} />
          <Route path="/lists/(.*)?" component={Tabs} />
        </Switch>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

if (container) {
  ReactDOM.render(<App />, container);
}
