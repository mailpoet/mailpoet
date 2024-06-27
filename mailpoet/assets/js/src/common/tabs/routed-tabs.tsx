import { Children, ReactElement } from 'react';
import {
  BrowserRouter,
  HashRouter,
  Redirect,
  Route,
  Routes,
  useNavigate,
  useLocation,
} from 'react-router-dom';
import { noop } from 'lodash';

import { Props as TabProps, Tabs } from './tabs';

function RouterAwareTabs(
  props: TabProps & {
    keyPathMap: { [key: string]: string };
    routerPrefix?: string;
  },
) {
  const navigate = useNavigate();
  const location = useLocation();

  const activeKey = Object.keys(props.keyPathMap).find(
    (key) => props.keyPathMap[key] === location.pathname,
  );

  return (
    <Tabs
      activeKey={activeKey}
      onSwitch={(tabKey) => {
        const path = `${props.routerPrefix}${tabKey}`;
        if (location && path !== location.pathname) {
          navigate(path);
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

  const routedTabs = (
    <Routes>
      {Object.values(keyPathMap).map((path) => (
        <Route
          path={path}
          element={
            <RouterAwareTabs
              activeKey={activeKey}
              onSwitch={onSwitch}
              automationId={automationId}
              keyPathMap={keyPathMap}
              routerPrefix={routerPrefix}
            >
              {children}
            </RouterAwareTabs>
          }
        />
      ))}

      <Route element={<Redirect to={`${routerPrefix}${activeKey}`} />} />
    </Routes>
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
