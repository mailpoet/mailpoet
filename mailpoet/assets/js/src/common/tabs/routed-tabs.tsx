import { Children } from 'react';
import {
  BrowserRouter,
  HashRouter,
  Navigate,
  Route,
  Routes,
  useNavigate,
  useLocation,
  matchPath,
} from 'react-router-dom';
import { noop } from 'lodash';

import { isTabElement, type Props as TabProps, Tabs } from './tabs';

function RouterAwareTabs(
  props: TabProps & {
    keyPathMap: { [key: string]: string };
    routerPrefix?: string;
  },
) {
  const navigate = useNavigate();
  const location = useLocation();

  const activeKey = Object.keys(props.keyPathMap).find((key) =>
    matchPath(props.keyPathMap[key], location.pathname),
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
  Children.forEach(children, (child) => {
    if (!child) {
      return;
    }

    if (!isTabElement(child)) {
      throw new Error(
        'Child components of <RoutedTabs> must be instances of <Tab>',
      );
    }

    keyPathMap[child.key] = `${routerPrefix}${child.props.route || child.key}`;
  });

  if (!keyPathMap[activeKey]) {
    throw new Error(
      `Child <Tab> with key ${activeKey} not found in <RoutedTabs> children`,
    );
  }

  if (routerType === 'switch-only') {
    return (
      <RouterAwareTabs
        activeKey={activeKey}
        onSwitch={onSwitch}
        automationId={automationId}
        keyPathMap={keyPathMap}
        routerPrefix={routerPrefix}
      >
        {children}
      </RouterAwareTabs>
    );
  }

  const routedTabs = (
    <Routes>
      {Object.values(keyPathMap).map((path) => (
        <Route
          key={path}
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

      <Route
        path="*"
        element={<Navigate to={`${routerPrefix}${activeKey}`} />}
      />
    </Routes>
  );

  return routerType === 'browser' ? (
    <BrowserRouter>{routedTabs}</BrowserRouter>
  ) : (
    <HashRouter>{routedTabs}</HashRouter>
  );
}

RoutedTabs.displayName = 'RoutedTabs';

export { RoutedTabs };
