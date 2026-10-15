import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

type RoutedTabsModule = {
  RoutedTabs: React.ComponentType<{
    activeKey: string;
    children?: React.ReactNode;
  }>;
};
type TabModule = {
  Tab: React.ComponentType<{ title: string; children?: React.ReactNode }>;
};
type TestRequire = {
  (path: string): unknown;
  cache: Record<string, unknown>;
  resolve: (path: string) => string;
};

const routedTabsPath = './assets/js/src/common/tabs/routed-tabs.tsx';
const tabPath = './assets/js/src/common/tabs/tab.tsx';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let routedTabsModule: RoutedTabsModule;
let tabModule: TabModule;

describe('RoutedTabs', function routedTabsSuite() {
  this.timeout(20000);

  before(() => {
    // A hash-less URL, as when the page is opened from the admin menu.
    dom = new JSDOM('<!doctype html><html><body></body></html>', {
      url: 'https://example.com/wp-admin/admin.php?page=mailpoet-settings',
    });
    global.window = dom.window as unknown as Window & typeof globalThis;
    global.document = dom.window.document;
    Object.defineProperty(global, 'navigator', {
      configurable: true,
      value: dom.window.navigator,
    });
    global.HTMLElement = dom.window.HTMLElement;
    global.Element = dom.window.Element;
    global.Node = dom.window.Node;
    global.IS_REACT_ACT_ENVIRONMENT = true;
    routedTabsModule = testRequire(routedTabsPath) as RoutedTabsModule;
    tabModule = testRequire(tabPath) as TabModule;
  });

  after(() => {
    delete testRequire.cache[testRequire.resolve(routedTabsPath)];
    dom.window.close();
    delete (global as { document?: unknown }).document;
    delete (global as { window?: unknown }).window;
    delete (global as { Element?: unknown }).Element;
    delete (global as { HTMLElement?: unknown }).HTMLElement;
    delete (global as { Node?: unknown }).Node;
    delete (global as { navigator?: unknown }).navigator;
    delete (global as { IS_REACT_ACT_ENVIRONMENT?: unknown })
      .IS_REACT_ACT_ENVIRONMENT;
  });

  it('opens the active tab without adding a history entry', async () => {
    const rootElement = document.createElement('div');
    document.body.appendChild(rootElement);
    const root = createRoot(rootElement);
    const historyLength = window.history.length;

    await React.act(async () => {
      root.render(
        React.createElement(
          routedTabsModule.RoutedTabs,
          { activeKey: 'basics' },
          React.createElement(
            tabModule.Tab,
            { key: 'basics', title: 'Basics' },
            'Basics content',
          ),
          React.createElement(
            tabModule.Tab,
            { key: 'signup', title: 'Sign-up' },
            'Sign-up content',
          ),
        ),
      );
    });

    expect(window.location.hash).to.equal('#/basics');
    expect(rootElement.textContent).to.contain('Basics content');
    // A pushed redirect would leave the hash-less entry behind, so Back would land on it and
    // redirect again instead of leaving the page.
    expect(window.history.length).to.equal(historyLength);

    await React.act(async () => {
      root.unmount();
    });
  });
});
