import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';
import sinon from 'sinon';

type StatsInfoModule = {
  NewsletterStatsInfo: React.ComponentType<{
    newsletter: Record<string, unknown>;
  }>;
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
type ElementProps = {
  children?: React.ReactNode;
  onClick?: () => void;
};

const statsInfoPath =
  './assets/js/src/newsletters/campaign-stats/newsletter-stats-info.tsx';
const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let statsInfoModule: StatsInfoModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;
const noticeSuccess = sinon.spy();

const wrapper = ({ children }: ElementProps) =>
  React.createElement('div', null, children);
const button = ({ children, onClick }: ElementProps) =>
  React.createElement('button', { type: 'button', onClick }, children);

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
    if (request === '@wordpress/components') {
      return {
        Button: button,
        ButtonGroup: wrapper,
        Dropdown: ({
          renderToggle,
          renderContent,
        }: {
          renderToggle: (args: Record<string, unknown>) => React.ReactNode;
          renderContent: () => React.ReactNode;
        }) =>
          React.createElement(
            'div',
            null,
            renderToggle({ isOpen: true, onToggle: () => undefined }),
            renderContent(),
          ),
        MenuGroup: wrapper,
        MenuItem: button,
        Modal: wrapper,
        TextControl: () => null,
      };
    }
    if (request === '@wordpress/icons') {
      return { chevronDown: null, Icon: () => null };
    }
    if (request === 'mailpoet') {
      return {
        MailPoet: {
          Date: { short: () => '', time: () => '' },
          Notice: { success: noticeSuccess, error: () => undefined },
          getActiveEmailEditorUrl: (newsletter: { id: number }) =>
            `admin.php?page=mailpoet-newsletter-editor&id=${newsletter.id}`,
        },
      };
    }
    if (request === 'common/typography/heading/heading') {
      return { Heading: wrapper };
    }
    if (request === 'common/grid') {
      return { Grid: { ThreeColumns: wrapper } };
    }
    if (request === 'common') {
      return {
        FilterSegmentTag: () => null,
        SegmentTags: () => null,
        Tag: wrapper,
        getNewsletterStatusString: () => 'Draft',
      };
    }
    if (request === 'newsletters/api') {
      return {
        duplicateNewsletter: () =>
          Promise.resolve({
            data: { id: 2, subject: 'Copy of Hello <img src=x>' },
          }),
      };
    }
    if (request === 'newsletters/listings/utils.jsx') {
      return { confirmEdit: () => undefined };
    }
    if (request === 'newsletters/timezone-campaign') {
      return { isTimezoneCampaignQueue: () => false };
    }
    if (request === 'newsletters/timezone-campaign-icon') {
      return { TimezoneCampaignIcon: () => null };
    }
    if (request === './export-button') {
      return { ExportButton: () => null };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

describe('NewsletterStatsInfo', function statsInfoSuite() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    statsInfoModule = testRequire(statsInfoPath) as StatsInfoModule;
    restoreModuleMocks();
  });

  after(() => {
    delete testRequire.cache[testRequire.resolve(statsInfoPath)];
    dom.window.close();
    delete (global as { document?: unknown }).document;
    delete (global as { window?: unknown }).window;
    delete (global as { Element?: unknown }).Element;
    delete (global as { HTMLElement?: unknown }).HTMLElement;
    delete (global as { Node?: unknown }).Node;
    delete (global as { navigator?: unknown }).navigator;
  });

  it('shows both subjects as text in the duplicated notice', async () => {
    const rootElement = document.createElement('div');
    document.body.appendChild(rootElement);
    root = createRoot(rootElement);
    await React.act(async () => {
      root.render(
        React.createElement(statsInfoModule.NewsletterStatsInfo, {
          newsletter: {
            id: 1,
            type: 'standard',
            status: 'draft',
            subject: 'Hello <img src=x>',
            segments: [],
          },
        }),
      );
    });

    const duplicateButton = Array.from(
      document.querySelectorAll('button'),
    ).find((element) => element.textContent === 'Duplicate');
    await React.act(async () => {
      duplicateButton.click();
    });

    expect(noticeSuccess.calledOnce).to.equal(true);
    expect(noticeSuccess.firstCall.args[0]).to.equal(
      'Email "Hello &lt;img src=x>" has been duplicated. New email: <a href="admin.php?page=mailpoet-newsletter-editor&id=2"> Copy of Hello &lt;img src=x> </a>',
    );

    await React.act(async () => {
      root.unmount();
    });
  });
});
