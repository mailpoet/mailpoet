import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

type StatisticsModule = {
  Statistics: React.ComponentType<Record<string, unknown>>;
};
type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = {
  (path: string): unknown;
  cache: Record<string, unknown>;
  resolve: (path: string) => string;
};

const statisticsPath = './assets/js/src/newsletters/listings/statistics.jsx';
const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let statisticsModule: StatisticsModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;

const setupWindow = () => {
  dom = new JSDOM('<!doctype html><html><body></body></html>', {
    url: 'https://example.com/wp-admin/admin.php',
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
};

const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === 'common') {
      return {
        Tag: ({ children }: { children: React.ReactNode }) =>
          React.createElement('span', { className: 'tag' }, children),
        withBoundary: (component: unknown) => component,
      };
    }
    if (request === 'wp-js-hooks') {
      return {
        Hooks: {
          applyFilters: (_name: string, value: unknown) => value,
        },
      };
    }
    if (request === 'common/listings/newsletter-stats') {
      return { NewsletterStats: () => null };
    }
    if (request === 'newsletters/listings/utils.jsx') {
      return { trackStatsCTAClicked: () => undefined };
    }
    if (request === 'react-router-dom') {
      return {
        Link: ({ children }: { children: React.ReactNode }) =>
          React.createElement('a', null, children),
      };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

const currentTime = '2026-01-01T12:00:00';

const renderBadgeText = async (sentAt: string) => {
  const rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);
  await React.act(async () => {
    root.render(
      React.createElement(statisticsModule.Statistics, {
        currentTime,
        newsletter: {
          id: 1,
          total_sent: 100,
          queue: {
            status: 'completed',
            count_processed: '100',
            count_total: '100',
            created_at: sentAt,
            scheduled_at: undefined,
          },
          statistics: { opened: 0, clicked: 0, notTracked: 0 },
        },
      }),
    );
  });
  return document.querySelector('.tag').textContent;
};

describe('listing stats "check back" badge', function statsBadge() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    statisticsModule = testRequire(statisticsPath) as StatisticsModule;
    restoreModuleMocks();
  });

  afterEach(async () => {
    if (root) {
      await React.act(async () => {
        root.unmount();
      });
    }
    document.body.innerHTML = '';
  });

  after(() => {
    delete testRequire.cache[testRequire.resolve(statisticsPath)];
    dom.window.close();
    [
      'document',
      'Element',
      'HTMLElement',
      'IS_REACT_ACT_ENVIRONMENT',
      'navigator',
      'Node',
      'window',
    ].forEach((property) => {
      delete (global as unknown as Record<string, unknown>)[property];
    });
  });

  it('uses the singular form when one hour is left', async () => {
    expect(await renderBadgeText('2026-01-01T07:00:00')).to.equal(
      'Nice job! Check back in 1 hour for more stats.',
    );
  });

  it('uses the plural form when several hours are left', async () => {
    expect(await renderBadgeText('2026-01-01T10:00:00')).to.equal(
      'Nice job! Check back in 4 hours for more stats.',
    );
  });

  it('never shows more than the timeout when the send date is in the future', async () => {
    expect(await renderBadgeText('2026-01-02T12:00:00')).to.equal(
      'Nice job! Check back in 6 hours for more stats.',
    );
  });
});
