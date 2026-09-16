import { JSDOM } from 'jsdom';
import NodeModule from 'module';

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = (path: string) => unknown;

type MailPoetModalType = {
  opened: boolean;
  prevFocus: unknown;
  options: Record<string, unknown>;
  popup: (opts: Record<string, unknown>) => MailPoetModalType;
  panel: (opts: Record<string, unknown>) => MailPoetModalType;
  close: () => MailPoetModalType;
  getFocusableElements: () => HTMLElement[];
};

type ModalModule = {
  MailPoetModal: MailPoetModalType;
};

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let originalModuleLoad: ModuleLoader;
let modalModule: ModalModule;

const setupWindow = () => {
  dom = new JSDOM(
    '<!doctype html><html><body><div class="wrap"><h1>Page heading</h1></div></body></html>',
    { url: 'https://example.com/wp-admin/admin.php' },
  );
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
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

const dispatchKey = (
  target: Element,
  type: 'keydown' | 'keyup',
  options: { key: string; keyCode: number; shiftKey?: boolean },
) => {
  const event = new dom.window.KeyboardEvent(type, {
    bubbles: true,
    cancelable: true,
    key: options.key,
    shiftKey: options.shiftKey || false,
  }) as KeyboardEvent;
  // jsdom's KeyboardEvent constructor does not set keyCode from init options.
  Object.defineProperty(event, 'keyCode', { get: () => options.keyCode });
  target.dispatchEvent(event);
  return event;
};

describe('MailPoetModal', function modalSuite() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    modalModule = testRequire('./assets/js/src/modal.js') as ModalModule;
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

  afterEach(() => {
    const { MailPoetModal } = modalModule;
    if (MailPoetModal.opened) {
      MailPoetModal.close();
    }
    document.body.innerHTML = '<div class="wrap"><h1>Page heading</h1></div>';
  });

  const openPopup = (opts: Record<string, unknown> = {}) => {
    modalModule.MailPoetModal.popup({
      template:
        '<button id="mailpoet_a">A</button><button id="mailpoet_b">B</button>',
      ...opts,
    });
  };

  it('focuses the initialFocus target on open', () => {
    const opener = document.createElement('button');
    opener.id = 'opener';
    document.body.appendChild(opener);
    opener.focus();

    openPopup({ initialFocus: '#mailpoet_b' });

    expect(document.activeElement && document.activeElement.id).to.equal(
      'mailpoet_b',
    );
  });

  // The close link (#mailpoet_modal_close) is itself focusable and precedes
  // the template content in the DOM, so it is the first focusable element.
  it('wraps focus from last to first element on Tab', () => {
    openPopup();
    const last = document.getElementById('mailpoet_b');
    last.focus();

    dispatchKey(last, 'keydown', { key: 'Tab', keyCode: 9 });

    expect(document.activeElement && document.activeElement.id).to.equal(
      'mailpoet_modal_close',
    );
  });

  it('wraps focus from first to last element on Shift+Tab', () => {
    openPopup();
    const first = document.getElementById('mailpoet_modal_close');
    first.focus();

    dispatchKey(first, 'keydown', {
      key: 'Tab',
      keyCode: 9,
      shiftKey: true,
    });

    expect(document.activeElement && document.activeElement.id).to.equal(
      'mailpoet_b',
    );
  });

  it('moves focus to the first element on Tab when the popup container itself is focused', () => {
    openPopup();
    const popup = document.getElementById('mailpoet_popup');
    popup.focus();

    const event = dispatchKey(popup, 'keydown', { key: 'Tab', keyCode: 9 });

    expect(document.activeElement && document.activeElement.id).to.equal(
      'mailpoet_modal_close',
    );
    expect(event.defaultPrevented).to.equal(true);
  });

  it('moves focus to the last element on Shift+Tab when the popup container itself is focused', () => {
    openPopup();
    const popup = document.getElementById('mailpoet_popup');
    popup.focus();

    const event = dispatchKey(popup, 'keydown', {
      key: 'Tab',
      keyCode: 9,
      shiftKey: true,
    });

    expect(document.activeElement && document.activeElement.id).to.equal(
      'mailpoet_b',
    );
    expect(event.defaultPrevented).to.equal(true);
  });

  it('pulls focus back into the popup when Tab fires outside it', () => {
    openPopup();
    document.body.focus();

    dispatchKey(document.body, 'keydown', { key: 'Tab', keyCode: 9 });

    expect(document.activeElement && document.activeElement.id).to.equal(
      'mailpoet_modal_close',
    );
  });

  it('leaves focus alone when Tab fires inside a WordPress media modal', () => {
    openPopup();
    const mediaModal = document.createElement('div');
    mediaModal.className = 'media-modal';
    const mediaButton = document.createElement('button');
    mediaModal.appendChild(mediaButton);
    document.body.appendChild(mediaModal);
    mediaButton.focus();

    const event = dispatchKey(mediaButton, 'keydown', {
      key: 'Tab',
      keyCode: 9,
    });

    expect(event.defaultPrevented).to.equal(false);
    expect(document.activeElement).to.equal(mediaButton);
  });

  it('runs onCancel and removes the popup on Escape', () => {
    let cancelled = false;
    openPopup({
      onCancel: () => {
        cancelled = true;
      },
    });

    dispatchKey(document.body, 'keyup', { key: 'Escape', keyCode: 27 });

    expect(cancelled).to.equal(true);
    expect(document.getElementById('mailpoet_popup')).to.equal(null);
  });

  it('returns focus to the opener on close', () => {
    const opener = document.createElement('button');
    opener.id = 'opener';
    document.body.appendChild(opener);
    opener.focus();

    openPopup();
    modalModule.MailPoetModal.close();

    expect(document.activeElement && document.activeElement.id).to.equal(
      'opener',
    );
  });

  it('focuses returnFocus when the opener is no longer in the document', () => {
    const opener = document.createElement('button');
    opener.id = 'opener';
    document.body.appendChild(opener);
    opener.focus();

    const fallbackTarget = document.createElement('button');
    fallbackTarget.id = 'fallback-target';
    document.body.appendChild(fallbackTarget);

    openPopup({ returnFocus: '#fallback-target' });
    opener.remove();
    modalModule.MailPoetModal.close();

    expect(document.activeElement && document.activeElement.id).to.equal(
      'fallback-target',
    );
  });

  it('falls back to the page heading when neither opener nor returnFocus exist', () => {
    const opener = document.createElement('button');
    opener.id = 'opener';
    document.body.appendChild(opener);
    opener.focus();

    openPopup();
    opener.remove();
    modalModule.MailPoetModal.close();

    const heading = document.querySelector('.wrap h1');
    expect(document.activeElement).to.equal(heading);
  });

  it('does not move focus to .wrap h1 when a panel closes with no previous focus', () => {
    const activeBeforeOpen = document.activeElement;

    modalModule.MailPoetModal.panel({
      template: '<button id="mailpoet_panel_btn">Panel</button>',
    });
    modalModule.MailPoetModal.close();

    const heading = document.querySelector('.wrap h1');
    expect(document.activeElement).to.not.equal(heading);
    expect(document.activeElement).to.equal(activeBeforeOpen);
  });

  it('excludes a focusable element inside a hidden ancestor from the focus list', () => {
    openPopup({
      template:
        '<button id="mailpoet_a">A</button>' +
        '<div id="hidden_wrapper" style="display: none;">' +
        '<button id="mailpoet_hidden">Hidden</button>' +
        '</div>',
    });

    const ids = modalModule.MailPoetModal.getFocusableElements().map(
      (el) => el.id,
    );

    expect(ids).to.not.include('mailpoet_hidden');
    expect(ids).to.include('mailpoet_a');
  });
});
