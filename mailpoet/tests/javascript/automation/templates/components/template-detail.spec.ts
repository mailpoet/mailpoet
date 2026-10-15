import { JSDOM, VirtualConsole } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';
import sinon from 'sinon';

type Template = {
  slug: string;
  name: string;
  description: string;
  category: string;
  type: string;
};

type TemplateDetailModule = {
  TemplateDetail: React.ComponentType<{
    template: Template;
    onRequestClose: () => void;
    onPreviousClick?: () => void;
    onNextClick?: () => void;
  }>;
};

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = (path: string) => unknown;

type FakeButtonProps = {
  children?: React.ReactNode;
  disabled?: boolean;
  isBusy?: boolean;
  onClick?: () => void;
  'aria-label'?: string;
};

type FakeModalProps = {
  children?: React.ReactNode;
  isDismissible?: boolean;
  shouldCloseOnEsc?: boolean;
  shouldCloseOnClickOutside?: boolean;
};

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let detailModule: TemplateDetailModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;
let apiFetchStub: sinon.SinonStub;
let navigationAttempts: number;
let modalDismissal: Omit<FakeModalProps, 'children'>;

const template: Template = {
  slug: 'subscriber-welcome-email',
  name: 'Welcome new subscribers',
  description: 'Send a welcome email.',
  category: 'welcome',
  type: 'default',
};

const setupWindow = () => {
  // jsdom doesn't implement navigation, it reports each attempt as an error
  const virtualConsole = new VirtualConsole();
  virtualConsole.on('jsdomError', (error: Error) => {
    if (error.message.includes('navigation')) {
      navigationAttempts += 1;
    }
  });
  dom = new JSDOM('<!doctype html><html><body></body></html>', {
    url: 'https://example.com/wp-admin/admin.php',
    virtualConsole,
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
  disabled = false,
  isBusy = false,
  onClick = undefined,
  'aria-label': ariaLabel,
}: FakeButtonProps): JSX.Element {
  return React.createElement(
    'button',
    {
      'aria-label': ariaLabel,
      'data-busy': isBusy ? 'true' : 'false',
      disabled,
      onClick,
      type: 'button',
    },
    children,
  );
}

function FakeModal({
  children,
  isDismissible = true,
  shouldCloseOnEsc = true,
  shouldCloseOnClickOutside = true,
}: FakeModalProps): JSX.Element {
  modalDismissal = {
    isDismissible,
    shouldCloseOnEsc,
    shouldCloseOnClickOutside,
  };
  return React.createElement('div', { role: 'dialog' }, children);
}

const isModalClosable = (): boolean =>
  modalDismissal.isDismissible &&
  modalDismissal.shouldCloseOnEsc &&
  modalDismissal.shouldCloseOnClickOutside;

function FakeSnackbar({
  children,
}: {
  children: React.ReactNode;
}): JSX.Element {
  return React.createElement('div', { role: 'alert' }, children);
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
      return { Button: FakeButton, Modal: FakeModal, Snackbar: FakeSnackbar };
    }
    if (request === '@wordpress/i18n') {
      return { __: (text: string) => text };
    }
    if (request === '@wordpress/icons') {
      return { caution: null, chevronLeft: null, chevronRight: null };
    }
    if (request === '@woocommerce/components') {
      return { Tag: () => null };
    }
    if (request === '@wordpress/api-fetch') {
      return (...args: unknown[]) => apiFetchStub(...args) as Promise<unknown>;
    }
    if (request === '@wordpress/url') {
      return {
        addQueryArgs: (path: string, args: Record<string, string>) =>
          `${path}&${String(new dom.window.URLSearchParams(args))}`,
      };
    }
    if (request === './template-preview') {
      return { TemplatePreview: () => null };
    }
    if (request === './template-email-previews') {
      return { TemplateEmailPreviews: () => null };
    }
    if (request === '../config') {
      return { automationTemplateCategories: [] };
    }
    if (request === '../../../mailpoet') {
      return {
        MailPoet: {
          trackEvent: () => undefined,
          urls: {
            automationEditor:
              'https://example.com/wp-admin/admin.php?page=mailpoet-automation-editor',
          },
        },
      };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

const render = async () => {
  const rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);
  await React.act(async () => {
    root.render(
      React.createElement(detailModule.TemplateDetail, {
        template,
        onRequestClose: () => undefined,
        onPreviousClick: () => undefined,
        onNextClick: () => undefined,
      }),
    );
  });
};

const getButton = (label: string): HTMLButtonElement =>
  Array.from(document.querySelectorAll('button')).find(
    (button) =>
      button.textContent === label ||
      button.getAttribute('aria-label') === label,
  );

const clickStartBuilding = async () => {
  await React.act(async () => {
    getButton('Start building').dispatchEvent(
      new window.MouseEvent('click', { bubbles: true }),
    );
    await Promise.resolve();
    await Promise.resolve();
  });
};

describe('automation template detail', function templateDetail() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    detailModule = testRequire(
      './assets/js/src/automation/templates/components/template-detail.tsx',
    ) as TemplateDetailModule;
    restoreModuleMocks();
  });

  beforeEach(() => {
    apiFetchStub = sinon.stub();
    navigationAttempts = 0;
  });

  afterEach(async () => {
    if (root) {
      await React.act(async () => {
        root.unmount();
      });
    }
    sinon.restore();
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

  it('keeps the buttons disabled and the modal open while redirecting to the editor', async () => {
    apiFetchStub.resolves({ data: { id: '123' } });

    await render();
    expect(isModalClosable()).to.equal(true);
    await clickStartBuilding();

    expect(apiFetchStub.calledOnce).to.equal(true);
    expect(apiFetchStub.firstCall.args[0]).to.deep.include({
      path: '/automations/create-from-template',
      method: 'POST',
      data: { slug: 'subscriber-welcome-email' },
    });
    expect(navigationAttempts).to.equal(1);

    const startBuilding = getButton('Start building');
    expect(startBuilding.disabled).to.equal(true);
    expect(startBuilding.getAttribute('data-busy')).to.equal('true');
    expect(getButton('Cancel').disabled).to.equal(true);
    expect(getButton('Previous template').disabled).to.equal(true);
    expect(getButton('Next template').disabled).to.equal(true);
    expect(modalDismissal).to.deep.equal({
      isDismissible: false,
      shouldCloseOnEsc: false,
      shouldCloseOnClickOutside: false,
    });

    await clickStartBuilding();

    expect(apiFetchStub.calledOnce).to.equal(true);
    expect(navigationAttempts).to.equal(1);
  });

  it('enables the buttons again when creating the automation fails', async () => {
    apiFetchStub.rejects(new Error('Request failed'));

    await render();
    await clickStartBuilding();

    expect(document.querySelector('[role="alert"]').textContent).to.equal(
      'An error occurred while creating the automation. Please, try again.',
    );
    expect(navigationAttempts).to.equal(0);

    const startBuilding = getButton('Start building');
    expect(startBuilding.disabled).to.equal(false);
    expect(startBuilding.getAttribute('data-busy')).to.equal('false');
    expect(getButton('Cancel').disabled).to.equal(false);
    expect(getButton('Previous template').disabled).to.equal(false);
    expect(getButton('Next template').disabled).to.equal(false);
    expect(isModalClosable()).to.equal(true);

    await clickStartBuilding();

    expect(apiFetchStub.calledTwice).to.equal(true);
  });
});
