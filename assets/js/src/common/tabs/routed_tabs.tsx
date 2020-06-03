import React from 'react';
import {
  BrowserRouter,
  HashRouter,
  Redirect,
  Route,
  Switch,
  useHistory,
  useRouteMatch,
} from 'react-router-dom';

import Tabs, { Props as TabProps } from './tabs';

const RouterAwareTabs = (props: TabProps) => {
  const match = useRouteMatch();
  const history = useHistory();

  return (
    <Tabs
      activeKey={match.path.replace(/^\//, '')}
      onSwitch={(tabKey) => {
        const path = `/${tabKey}`;
        if (history.location && path !== history.location.pathname) {
          history.push(path);
        }
        props.onSwitch(tabKey);
      }}
    >
      {props.children}
    </Tabs>
  );
};

type Props = TabProps & {
  routerType?: 'hash' | 'browser',
};

const RoutedTabs = ({
  routerType = 'hash',
  activeKey,
  onSwitch = () => {},
  children,
}: Props) => {
  const keys = React.Children.map(
    children,
    (child: React.ReactElement) => (child ? child.key : null)
  ).filter((key) => key);

  if (!keys.includes(activeKey)) {
    throw new Error(`Child <Tab> with key ${activeKey} not found in <RoutedTabs> children`);
  }

  // Notes about performance:
  //  1. We use a single route with an array of all tab URLs (all render the same component).
  //  2. Using 'render' (not 'component') ensures it is reused even when wrapped in a callback.
  const routedTabs = (
    <>
      <Switch>
        <Route
          exact
          path={keys.map((key: string) => `/${key}`)}
          render={() => (
            <RouterAwareTabs activeKey={activeKey} onSwitch={onSwitch}>{children}</RouterAwareTabs>
          )}
        />
        <Redirect to={`/${activeKey}`} />
      </Switch>
    </>
  );

  return routerType === 'browser'
    ? <BrowserRouter>{routedTabs}</BrowserRouter>
    : <HashRouter>{routedTabs}</HashRouter>;
};

export default RoutedTabs;
