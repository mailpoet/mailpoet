import { expect } from 'chai';
import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

type AddSenderDomainModule = {
  AddSenderDomain: React.ComponentType<{
    senderDomain: string;
    loadingButton: boolean;
    addDomainButtonClicked: () => void;
    error?: string;
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

const patchedGlobals = [
  'window',
  'document',
  'navigator',
  'HTMLElement',
  'Element',
  'Node',
  'IS_REACT_ACT_ENVIRONMENT',
] as const;

let dom: JSDOM;
let addSenderDomainModule: AddSenderDomainModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;
let rootElement: HTMLDivElement;
let clicks = 0;
let savedGlobals: Record<string, PropertyDescriptor | undefined> = {};

const restoreGlobals = () => {
  patchedGlobals.forEach((key) => {
    const saved = savedGlobals[key];
    if (saved) {
      Object.defineProperty(global, key, saved);
    } else {
      delete global[key];
    }
  });
  savedGlobals = {};
};

const setupWindow = () => {
  savedGlobals = {};
  patchedGlobals.forEach((key) => {
    savedGlobals[key] = Object.getOwnPropertyDescriptor(global, key);
  });

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

function FakeButton({
  children,
  onClick,
  isBusy,
  disabled,
}: {
  children?: React.ReactNode;
  onClick?: () => void;
  isBusy?: boolean;
  disabled?: boolean;
}): JSX.Element {
  return React.createElement(
    'button',
    {
      type: 'button',
      onClick,
      disabled,
      'data-busy': isBusy ? 'true' : 'false',
    },
    children,
  );
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
      return { Button: FakeButton };
    }
    if (request === '@wordpress/i18n') {
      return {
        __: (text: string) => text,
        sprintf: (format: string, ...args: string[]) =>
          format.replace('%s', args[0]),
      };
    }
    if (request === '@wordpress/element') {
      // Stubbed rather than loaded: the real package reaches into the shared globals, which
      // breaks the suites that run after this one.
      return { createInterpolateElement: (text: string) => text };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

const render = async (props: { error?: string; loadingButton?: boolean }) => {
  rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);
  await React.act(async () => {
    root.render(
      React.createElement(addSenderDomainModule.AddSenderDomain, {
        senderDomain: 'example.com',
        loadingButton: props.loadingButton ?? false,
        addDomainButtonClicked: () => {
          clicks += 1;
        },
        error: props.error,
      }),
    );
  });
};

const button = () => document.querySelector('button');

describe('add sender domain panel', function addSenderDomainPanel() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    addSenderDomainModule = testRequire(
      './assets/js/src/common/manage-sender-domain/add-sender-domain.tsx',
    ) as AddSenderDomainModule;
    restoreModuleMocks();
  });

  beforeEach(() => {
    clicks = 0;
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
    restoreGlobals();
  });

  it('names the domain it is about to add', async () => {
    await render({});

    expect(document.body.textContent).to.contain('example.com');
    expect(document.body.textContent).to.contain(
      'has not been added to your account yet',
    );
  });

  it('adds the domain on click rather than on render', async () => {
    await render({});

    expect(clicks).to.equal(0);

    await React.act(async () => {
      button().click();
      await Promise.resolve();
    });

    expect(clicks).to.equal(1);
  });

  it('marks the button busy and disabled while the request is in flight', async () => {
    await render({ loadingButton: true });

    expect(button().getAttribute('data-busy')).to.equal('true');
    expect(button().disabled).to.equal(true);
  });

  it('leaves the button enabled when nothing is in flight', async () => {
    await render({});

    expect(button().disabled).to.equal(false);
  });

  it('shows what went wrong when adding the domain fails', async () => {
    await render({ error: 'Invalid API key' });

    expect(document.body.textContent).to.contain(
      'Error adding your sender domain.',
    );
    expect(document.body.textContent).to.contain('Invalid API key');
  });
});
