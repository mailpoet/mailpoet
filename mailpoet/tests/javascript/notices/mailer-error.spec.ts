import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

type MailerErrorModule = {
  MailerError: React.ComponentType<{
    mtaLog: {
      status: string | null;
      error: { operation: string; error_message: string } | null;
    };
    mtaMethod: string;
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
let mailerErrorModule: MailerErrorModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;
let resumeCalls = 0;

const fakeRequest = {
  done: () => fakeRequest,
  fail: () => fakeRequest,
  then: (callback: () => void) => {
    callback();
    return { catch: () => undefined };
  },
};

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
    if (request === '@wordpress/i18n') {
      return { __: (text: string) => text };
    }
    if (request === 'mailpoet') {
      return {
        MailPoet: {
          Ajax: {
            post: () => {
              resumeCalls += 1;
              return fakeRequest;
            },
          },
          Notice: {
            success: () => undefined,
            showApiErrorNotice: () => undefined,
          },
          MailPoetComUrlFactory: { getFreePlanUrl: () => '' },
          hasInvalidMssApiKey: false,
          emailVolumeLimitReached: false,
          subscribersLimitReached: false,
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

const renderNotice = async (operation: string, mtaMethod = 'MailPoet') => {
  const rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);
  await React.act(async () => {
    root.render(
      React.createElement(mailerErrorModule.MailerError, {
        mtaLog: {
          status: 'paused',
          error: { operation, error_message: 'Sending problem' },
        },
        mtaMethod,
      }),
    );
  });
};

const setPendingFlag = (value: string) => {
  (
    window as unknown as Record<string, unknown>
  ).mailpoet_mss_key_pending_approval = value;
};

describe('mailer error notice', function mailerErrorNotice() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    mailerErrorModule = testRequire(
      './assets/js/src/notices/mailer-error.tsx',
    ) as MailerErrorModule;
    restoreModuleMocks();
  });

  beforeEach(() => {
    resumeCalls = 0;
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
    ].forEach((property) => {
      delete (global as unknown as Record<string, unknown>)[property];
    });
  });

  it('shows the stored message as an error without a resume button while the key is still pending approval', async () => {
    setPendingFlag('1');
    await renderNotice('pending_approval');
    expect(document.querySelector('.notice-error').textContent).to.include(
      'Sending problem',
    );
    expect(document.querySelector('.notice-warning')).to.equal(null);
    expect(document.querySelector('a.button-primary')).to.equal(null);
  });

  it('offers to resume sending as a warning once the key is no longer pending approval', async () => {
    setPendingFlag('');
    await renderNotice('pending_approval');
    const notice = document.querySelector('.notice-warning');
    expect(notice.textContent).to.include('no longer pending approval');
    expect(notice.textContent).to.not.include('Sending problem');
    expect(document.querySelector('.notice-error')).to.equal(null);
    expect(document.querySelector('a.button-primary').textContent).to.equal(
      'Resume sending',
    );
  });

  it('resumes sending and removes the notice when the resume button is clicked', async () => {
    setPendingFlag('');
    await renderNotice('pending_approval');
    await React.act(async () => {
      document
        .querySelector('a.button-primary')
        .dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    });
    expect(resumeCalls).to.equal(1);
    expect(document.body.textContent).to.equal('');
  });

  it('keeps the upgrade wording for the subscriber limit notice', async () => {
    await renderNotice('subscriber_limit_reached');
    expect(document.querySelector('a.button-primary').textContent).to.equal(
      'I have upgraded my subscription, resume sending',
    );
  });

  it('keeps the resume button for a generic sending error', async () => {
    await renderNotice('send', 'SMTP');
    expect(document.querySelector('a.button-primary').textContent).to.equal(
      'Resume sending',
    );
  });
});
