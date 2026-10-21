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
  accessibleWhenDisabled?: boolean;
  isBusy?: boolean;
  variant?: string;
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

// created automations are remembered per template slug for the whole page,
// so every test uses its own slug
let templateCount = 0;
let template: Template;
const baseTemplate: Template = {
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

// mirrors how the real Button handles accessibleWhenDisabled
function FakeButton({
  children,
  disabled = false,
  accessibleWhenDisabled = false,
  isBusy = false,
  variant = undefined,
  onClick = undefined,
  'aria-label': ariaLabel,
}: FakeButtonProps): JSX.Element {
  const focusableWhenDisabled = disabled && accessibleWhenDisabled;
  return React.createElement(
    'button',
    {
      'aria-label': ariaLabel,
      'aria-disabled': focusableWhenDisabled ? 'true' : undefined,
      'data-busy': isBusy ? 'true' : 'false',
      'data-variant': variant,
      disabled: disabled && !accessibleWhenDisabled,
      onClick: focusableWhenDisabled ? undefined : onClick,
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

const render = async (
  onPreviousClick: () => void = () => undefined,
  onNextClick: () => void = () => undefined,
) => {
  const rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);
  await React.act(async () => {
    root.render(
      React.createElement(detailModule.TemplateDetail, {
        template,
        onRequestClose: () => undefined,
        onPreviousClick,
        onNextClick,
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

// Start building is the only primary button; its label changes while busy
const getStartBuilding = (): HTMLButtonElement =>
  document.querySelector('button[data-variant="primary"]');

const isDisabled = (button: HTMLButtonElement): boolean =>
  button.disabled || button.getAttribute('aria-disabled') === 'true';

const pressKey = async (key: string) => {
  await React.act(async () => {
    document.dispatchEvent(
      new window.KeyboardEvent('keydown', { key, bubbles: true }),
    );
  });
};

const clickStartBuilding = async () => {
  await React.act(async () => {
    getStartBuilding().dispatchEvent(
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
    templateCount += 1;
    template = {
      ...baseTemplate,
      slug: `${baseTemplate.slug}-${templateCount}`,
    };
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

  it('keeps Start building busy while redirecting to the editor', async () => {
    apiFetchStub.resolves({ data: { id: '123' } });

    await render();
    await clickStartBuilding();

    expect(apiFetchStub.calledOnce).to.equal(true);
    expect(apiFetchStub.firstCall.args[0]).to.deep.include({
      path: '/automations/create-from-template',
      method: 'POST',
      data: { slug: template.slug },
    });
    expect(navigationAttempts).to.equal(1);

    const startBuilding = getStartBuilding();
    expect(startBuilding.textContent).to.equal('Creating automation…');
    expect(isDisabled(startBuilding)).to.equal(true);
    expect(startBuilding.getAttribute('data-busy')).to.equal('true');
    expect(getButton('Previous template').disabled).to.equal(true);
    expect(getButton('Next template').disabled).to.equal(true);
    // the user can still leave the modal if the editor doesn't open
    expect(getButton('Cancel').disabled).to.equal(false);
    expect(isModalClosable()).to.equal(true);

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

    const startBuilding = getStartBuilding();
    expect(startBuilding.textContent).to.equal('Start building');
    expect(isDisabled(startBuilding)).to.equal(false);
    expect(startBuilding.getAttribute('data-busy')).to.equal('false');
    expect(getButton('Cancel').disabled).to.equal(false);
    expect(getButton('Previous template').disabled).to.equal(false);
    expect(getButton('Next template').disabled).to.equal(false);

    await clickStartBuilding();

    expect(apiFetchStub.calledTwice).to.equal(true);
  });

  it('keeps Start building focusable while creating the automation', async () => {
    apiFetchStub.returns(
      new Promise(() => {
        // keep the request pending
      }),
    );

    await render();
    await clickStartBuilding();

    // a disabled button would drop focus to the body, so Escape would no
    // longer reach the modal after an error
    const startBuilding = getStartBuilding();
    expect(startBuilding.disabled).to.equal(false);
    expect(startBuilding.getAttribute('aria-disabled')).to.equal('true');
  });

  it('switches templates with the arrow keys', async () => {
    const clicks = { previous: 0, next: 0 };

    await render(
      () => {
        clicks.previous += 1;
      },
      () => {
        clicks.next += 1;
      },
    );
    await pressKey('ArrowLeft');
    await pressKey('ArrowRight');

    expect(clicks).to.deep.equal({ previous: 1, next: 1 });
  });

  it('ignores the arrow keys while redirecting to the editor', async () => {
    apiFetchStub.resolves({ data: { id: '123' } });
    const clicks = { previous: 0, next: 0 };

    await render(
      () => {
        clicks.previous += 1;
      },
      () => {
        clicks.next += 1;
      },
    );
    await clickStartBuilding();
    await pressKey('ArrowLeft');
    await pressKey('ArrowRight');

    expect(clicks).to.deep.equal({ previous: 0, next: 0 });
  });

  it('opens the created automation after the modal was closed and reopened', async () => {
    apiFetchStub.resolves({ data: { id: '123' } });

    await render();
    await clickStartBuilding();
    await React.act(async () => {
      root.unmount();
    });
    await render();
    await clickStartBuilding();

    expect(apiFetchStub.calledOnce).to.equal(true);
    expect(navigationAttempts).to.equal(2);
  });

  it('waits for a pending create after the modal was closed and reopened', async () => {
    let resolveCreate: (value: unknown) => void = () => undefined;
    apiFetchStub.returns(
      new Promise((resolve) => {
        resolveCreate = resolve;
      }),
    );

    await render();
    await clickStartBuilding();
    await React.act(async () => {
      root.unmount();
    });
    await render();
    await clickStartBuilding();
    await React.act(async () => {
      resolveCreate({ data: { id: '123' } });
      await Promise.resolve();
      await Promise.resolve();
    });

    expect(apiFetchStub.calledOnce).to.equal(true);
  });

  it('creates the automation again after a response without data', async () => {
    apiFetchStub.onFirstCall().resolves(null);
    apiFetchStub.onSecondCall().resolves({ data: { id: '123' } });

    await render();
    await clickStartBuilding();

    expect(document.querySelector('[role="alert"]')).to.not.equal(null);
    expect(navigationAttempts).to.equal(0);

    await clickStartBuilding();

    expect(apiFetchStub.calledTwice).to.equal(true);
    expect(navigationAttempts).to.equal(1);
  });
});
