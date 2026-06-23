import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

type ModalModule = {
  EmailPreviewModal: React.ComponentType<{
    onClose: () => void;
    src?: string;
    srcDoc?: string;
  }>;
};

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = (path: string) => unknown;

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let modalModule: ModalModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;
let rootElement: HTMLDivElement;

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

function FakeModal({ children }: { children?: React.ReactNode }): JSX.Element {
  return React.createElement('div', { role: 'dialog' }, children);
}

function FakeSpinner(): JSX.Element {
  return React.createElement('span', {
    'data-automation-id': 'email_preview_spinner',
  });
}

const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === '@wordpress/components') {
      return { Modal: FakeModal, Spinner: FakeSpinner };
    }
    if (request === '@wordpress/i18n') {
      return { __: (text: string) => text };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

const renderModal = async (srcDoc: string) => {
  rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);
  await React.act(async () => {
    root.render(
      React.createElement(modalModule.EmailPreviewModal, {
        onClose: () => undefined,
        srcDoc,
      }),
    );
  });
};

const spinner = () =>
  document.querySelector('[data-automation-id="email_preview_spinner"]');

const iframe = () =>
  document.querySelector(
    '[data-automation-id="automation_send_email_preview_iframe"]',
  );

const fireIframeLoad = async () => {
  await React.act(async () => {
    iframe().dispatchEvent(new window.Event('load'));
    await Promise.resolve();
  });
};

describe('email preview modal', function emailPreviewModal() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    modalModule = testRequire(
      './assets/js/src/automation/components/email-preview-modal/index.tsx',
    ) as ModalModule;
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
    dom.window.close();
    [
      'document',
      'Element',
      'HTMLElement',
      'IS_REACT_ACT_ENVIRONMENT',
      'navigator',
      'Node',
      'window',
    ].forEach((property) => Reflect.deleteProperty(global, property));
  });

  it('shows the spinner again when the source changes', async () => {
    await renderModal('<p>a</p>');
    await fireIframeLoad();
    expect(spinner()).to.equal(null);

    // Swap the content on the already-mounted modal.
    await React.act(async () => {
      root.render(
        React.createElement(modalModule.EmailPreviewModal, {
          onClose: () => undefined,
          srcDoc: '<p>b</p>',
        }),
      );
    });

    expect(spinner()).to.not.equal(null);
  });
});
