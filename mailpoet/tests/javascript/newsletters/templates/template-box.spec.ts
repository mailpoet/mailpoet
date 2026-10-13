import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';
import sinon from 'sinon';

type TemplateBoxModule = {
  TemplateBox: React.ComponentType<Record<string, unknown>>;
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

const templateBoxPath =
  './assets/js/src/newsletters/templates/template-box.jsx';
const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let templateBoxModule: TemplateBoxModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;
const modalPopup = sinon.spy();

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
    if (request === 'mailpoet') {
      return { MailPoet: { Modal: { popup: modalPopup } } };
    }
    if (request === 'common/button/button') {
      return {
        Button: ({ children }: { children: React.ReactNode }) =>
          React.createElement('span', null, children),
      };
    }
    if (request === 'common/template-box/template-box') {
      return {
        TemplateBox: ({ children }: { children: React.ReactNode }) =>
          React.createElement('div', null, children),
      };
    }
    if (request === 'common/confirm-alert.jsx') {
      return { confirmAlert: () => undefined };
    }
    if (request === 'context') {
      return { GlobalContext: React.createContext({}) };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

describe('TemplateBox', function templateBoxSuite() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    templateBoxModule = testRequire(templateBoxPath) as TemplateBoxModule;
    restoreModuleMocks();
  });

  after(() => {
    delete testRequire.cache[testRequire.resolve(templateBoxPath)];
    dom.window.close();
    delete (global as { document?: unknown }).document;
    delete (global as { window?: unknown }).window;
    delete (global as { Element?: unknown }).Element;
    delete (global as { HTMLElement?: unknown }).HTMLElement;
    delete (global as { Node?: unknown }).Node;
    delete (global as { navigator?: unknown }).navigator;
  });

  it('passes the template name as text to the preview title', async () => {
    const rootElement = document.createElement('div');
    document.body.appendChild(rootElement);
    root = createRoot(rootElement);
    await React.act(async () => {
      root.render(
        React.createElement(templateBoxModule.TemplateBox, {
          index: 0,
          id: 1,
          newsletterId: '1',
          name: 'Sale <img src=x> & more',
          thumbnail: 'https://example.com/thumbnail.png',
          readonly: false,
          beforeDelete: () => undefined,
          afterDelete: () => undefined,
          beforeSelect: () => undefined,
          afterSelect: () => undefined,
        }),
      );
    });

    await React.act(async () => {
      document.querySelector<HTMLElement>('.mailpoet-template-preview').click();
    });

    expect(modalPopup.calledOnce).to.equal(true);
    expect(modalPopup.firstCall.args[0].title).to.equal(
      'Sale &lt;img src=x> &amp; more',
    );

    await React.act(async () => {
      root.unmount();
    });
  });
});
