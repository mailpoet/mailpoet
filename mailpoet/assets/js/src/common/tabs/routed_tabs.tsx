import { Children, ReactElement } from 'react';
import {
  BrowserRouter,
  HashRouter,
  Redirect,
  Route,
  Switch,
  useHistory,
  useRouteMatch,
} from 'react-router-dom';
import { noop } from 'lodash';

import { Props as TabProps, Tabs } from './tabs';

function RouterAwareTabs(
  props: TabProps & {
    keyPathMap: { [key: string]: string };
    routerPrefix?: string;
  },
) {
  const match = useRouteMatch();
  const history = useHistory();

  const activeKey = Object.keys(props.keyPathMap).find(
    (key) => props.keyPathMap[key] === match.path,
  );

  return (
    <Tabs
      activeKey={activeKey}
      onSwitch={(tabKey) => {
        const path = `${props.routerPrefix}${tabKey}`;
        if (history.location && path !== history.location.pathname) {
          history.push(path);
        }
        props.onSwitch(tabKey);
      }}
      automationId={props.automationId}
    >
      {props.children}
    </Tabs>
  );
}

type Props = TabProps & {
  routerType?: 'hash' | 'browser' | 'switch-only';
  routerPrefix?: string;
};

function RoutedTabs({
  routerType = 'hash',
  routerPrefix = '/',
  activeKey,
  onSwitch = noop,
  automationId = null,
  children,
}: Props) {
  const keyPathMap: { [key: string]: string } = {};
  Children.map(children, (child: ReactElement) => {
    if (child) {
      keyPathMap[child.key] = `${routerPrefix}${
        child.props.route || child.key
      }`;
    }
  });

  if (!keyPathMap[activeKey]) {
    throw new Error(
      `Child <Tab> with key ${activeKey} not found in <RoutedTabs> children`,
    );
  }

  // Notes about performance:
  //  1. We use a single route with an array of all tab URLs (all render the same component).
  //  2. Using 'render' (not 'component') ensures it is reused even when wrapped in a callback.
  const routedTabs = (
    <Switch>
      <Route
        exact
        path={Object.values(keyPathMap)}
        render={() => (
          <RouterAwareTabs
            activeKey={activeKey}
            onSwitch={onSwitch}
            automationId={automationId}
            keyPathMap={keyPathMap}
            routerPrefix={routerPrefix}
          >
            {children}
          </RouterAwareTabs>
        )}
      />
      <Redirect to={`${routerPrefix}${activeKey}`} />
    </Switch>
  );

  if (routerType === 'switch-only') {
    return routedTabs;
  }

  return routerType === 'browser' ? (
    <BrowserRouter>{routedTabs}</BrowserRouter>
  ) : (
    <HashRouter>{routedTabs}</HashRouter>
  );
}

RoutedTabs.displayName = 'RoutedTabs';

export { RoutedTabs };
