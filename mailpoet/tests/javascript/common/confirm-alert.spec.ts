import { JSDOM } from 'jsdom';
import NodeModule from 'module';

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = (path: string) => unknown;

type ConfirmAlertModule = {
  confirmAlert: (props: {
    message: string;
    onConfirm: () => unknown;
    title?: string;
    cancelLabel?: string;
    confirmLabel?: string;
    returnFocus?: string | (() => Element | null);
  }) => void;
};

type PopupOptions = {
  initialFocus?: string;
  returnFocus?: string | (() => Element | null);
  onInit?: () => void;
};

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let originalModuleLoad: ModuleLoader;
let confirmAlertModule: ConfirmAlertModule;
let lastPopupOptions: PopupOptions | null;
let closeCalls: number;

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
};

const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === '@wordpress/i18n') {
      return { __: (text: string) => text };
    }
    if (request === 'mailpoet') {
      return {
        MailPoet: {
          Modal: {
            popup: (opts: PopupOptions) => {
              lastPopupOptions = opts;
            },
            close: () => {
              closeCalls += 1;
            },
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

const clickEvent = () =>
  new dom.window.MouseEvent('click', { bubbles: true }) as MouseEvent;

describe('confirmAlert', function confirmAlertSuite() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    confirmAlertModule = testRequire(
      './assets/js/src/common/confirm-alert.jsx',
    ) as ConfirmAlertModule;
    restoreModuleMocks();
  });

  after(() => {
    dom.window.close();
    delete (global as { document?: unknown }).document;
    delete (global as { window?: unknown }).window;
    delete (global as { Element?: unknown }).Element;
    delete (global as { HTMLElement?: unknown }).HTMLElement;
    delete (global as { Node?: unknown }).Node;
    delete (global as { navigator?: unknown }).navigator;
  });

  beforeEach(() => {
    lastPopupOptions = null;
    closeCalls = 0;
    document.body.innerHTML =
      '<button id="mailpoet_alert_cancel"></button>' +
      '<button id="mailpoet_alert_confirm"></button>';
  });

  it('asks the popup to focus Cancel first', () => {
    confirmAlertModule.confirmAlert({
      message: 'Are you sure?',
      onConfirm: () => undefined,
    });

    expect(lastPopupOptions && lastPopupOptions.initialFocus).to.equal(
      '#mailpoet_alert_cancel',
    );
  });

  // The actual Cancel/Escape restoreFocus fallback chain (returnFocus wins
  // over the page heading when the opener isn't focusable) is covered
  // generically by modal.spec.ts's "falls back to returnFocus when the
  // popup was opened with body focused" test. What's specific to
  // confirmAlert -- and was the bug here -- is that returnFocus actually
  // reaches popup()'s options, so modal.js has something to fall back to.
  it('passes returnFocus through to the popup options, so Cancel/Escape can use it', () => {
    const returnFocus = '#some-target';
    confirmAlertModule.confirmAlert({
      message: 'Are you sure?',
      onConfirm: () => undefined,
      returnFocus,
    });

    expect(lastPopupOptions && lastPopupOptions.returnFocus).to.equal(
      returnFocus,
    );
  });

  it('closes the modal and calls onConfirm on confirm click', () => {
    let confirmed = false;
    confirmAlertModule.confirmAlert({
      message: 'Are you sure?',
      onConfirm: () => {
        confirmed = true;
      },
    });

    lastPopupOptions.onInit();
    document
      .getElementById('mailpoet_alert_confirm')
      .dispatchEvent(clickEvent());

    expect(closeCalls).to.equal(1);
    expect(confirmed).to.equal(true);
  });

  it('closes the modal on cancel click without calling onConfirm', () => {
    let confirmed = false;
    confirmAlertModule.confirmAlert({
      message: 'Are you sure?',
      onConfirm: () => {
        confirmed = true;
      },
    });

    lastPopupOptions.onInit();
    document
      .getElementById('mailpoet_alert_cancel')
      .dispatchEvent(clickEvent());

    expect(closeCalls).to.equal(1);
    expect(confirmed).to.equal(false);
  });

  it('focuses returnFocus after the next tick following a sync onConfirm', async () => {
    const target = document.createElement('button');
    target.id = 'return-target';
    document.body.appendChild(target);

    confirmAlertModule.confirmAlert({
      message: 'Are you sure?',
      onConfirm: () => undefined,
      returnFocus: '#return-target',
    });

    lastPopupOptions.onInit();
    document
      .getElementById('mailpoet_alert_confirm')
      .dispatchEvent(clickEvent());

    // focus is deferred to the next tick (setTimeout(0))
    await new Promise((resolve) => {
      setTimeout(resolve, 0);
    });

    expect(document.activeElement && document.activeElement.id).to.equal(
      'return-target',
    );
  });

  it('focuses returnFocus after a promise-returning onConfirm settles', async () => {
    const target = document.createElement('button');
    target.id = 'return-target-async';
    document.body.appendChild(target);

    confirmAlertModule.confirmAlert({
      message: 'Are you sure?',
      onConfirm: () => Promise.resolve(),
      returnFocus: () => document.getElementById('return-target-async'),
    });

    lastPopupOptions.onInit();
    document
      .getElementById('mailpoet_alert_confirm')
      .dispatchEvent(clickEvent());

    // let the onConfirm() promise settle, then the deferred focus tick run
    await Promise.resolve();
    await Promise.resolve();
    await new Promise((resolve) => {
      setTimeout(resolve, 0);
    });

    expect(document.activeElement && document.activeElement.id).to.equal(
      'return-target-async',
    );
  });

  it('does not focus returnFocus while it is still disabled, and focuses it once re-enabled on the next tick', async () => {
    const target = document.createElement('button');
    target.id = 'return-target-disabled';
    target.disabled = true;
    document.body.appendChild(target);

    confirmAlertModule.confirmAlert({
      message: 'Are you sure?',
      onConfirm: () => Promise.resolve(),
      returnFocus: () => document.getElementById('return-target-disabled'),
    });

    lastPopupOptions.onInit();
    document
      .getElementById('mailpoet_alert_confirm')
      .dispatchEvent(clickEvent());

    // let the onConfirm() promise settle, but the target is still disabled
    await Promise.resolve();
    await Promise.resolve();

    expect(document.activeElement && document.activeElement.id).to.not.equal(
      'return-target-disabled',
    );

    // simulate React committing the re-render that re-enables the field
    target.disabled = false;

    // let the deferred focus (setTimeout(0)) run
    await new Promise((resolve) => {
      setTimeout(resolve, 0);
    });

    expect(document.activeElement && document.activeElement.id).to.equal(
      'return-target-disabled',
    );
  });

  it('focuses returnFocus after a rejected onConfirm settles', async () => {
    const target = document.createElement('button');
    target.id = 'return-target-rejected';
    document.body.appendChild(target);

    confirmAlertModule.confirmAlert({
      message: 'Are you sure?',
      onConfirm: () => Promise.reject(new Error('boom')),
      returnFocus: () => document.getElementById('return-target-rejected'),
    });

    lastPopupOptions.onInit();
    document
      .getElementById('mailpoet_alert_confirm')
      .dispatchEvent(clickEvent());

    // let the rejected onConfirm() promise settle, then the deferred focus tick run
    await Promise.resolve();
    await Promise.resolve();
    await new Promise((resolve) => {
      setTimeout(resolve, 0);
    });

    expect(document.activeElement && document.activeElement.id).to.equal(
      'return-target-rejected',
    );
  });
});
