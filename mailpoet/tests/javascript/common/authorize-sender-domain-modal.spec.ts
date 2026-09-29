import { expect } from 'chai';
import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

type ModalModule = {
  AuthorizeSenderDomainModal: React.ComponentType<{
    senderDomain: string;
    onRequestClose: () => void;
    useModal: boolean;
  }>;
};

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = (path: string) => unknown;
type AjaxCall = { action: string; data: { domain: string } };

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let modalModule: ModalModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;
let rootElement: HTMLDivElement;
let ajaxCalls: AjaxCall[] = [];
// What the read-only fetch returns: no records for a domain that isn't on the account.
let recordsOnAccount: Array<{ host: string }> = [];
// What the create returns: the records generated for the newly added domain.
let recordsCreated: Array<{ host: string }> = [];
// When set, the create request never resolves, as if it were still in flight.
let holdCreate = false;

const patchedGlobals = [
  'window',
  'document',
  'navigator',
  'HTMLElement',
  'Element',
  'Node',
  'IS_REACT_ACT_ENVIRONMENT',
] as const;

let savedGlobals: Record<string, PropertyDescriptor | undefined> = {};

/**
 * Specs share one Node process, so anything this suite puts on `global` outlives it and reaches
 * the suites that run afterwards. Put the previous descriptors back rather than deleting.
 */
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

function FakeModal({ children }: { children?: React.ReactNode }): JSX.Element {
  return React.createElement('div', { role: 'dialog' }, children);
}

function FakeSpinner(): JSX.Element {
  return React.createElement('span', { 'data-automation-id': 'spinner' });
}

function FakeButton({
  children,
  onClick,
}: {
  children?: React.ReactNode;
  onClick?: () => void;
}): JSX.Element {
  return React.createElement('button', { type: 'button', onClick }, children);
}

function FakeAddSenderDomain({
  addDomainButtonClicked,
}: {
  addDomainButtonClicked: () => void;
}): JSX.Element {
  return React.createElement(
    'button',
    { type: 'button', onClick: addDomainButtonClicked },
    'Add domain',
  );
}

function FakeManageSenderDomain({
  rows,
}: {
  rows: Array<{ domain: string; dns: Array<{ host: string }> }>;
}): JSX.Element {
  return React.createElement(
    'div',
    {
      'data-automation-id': 'manage_sender_domain',
      'data-hosts': rows.length
        ? rows[0].dns.map((record) => record.host).join(',')
        : '',
    },
    rows.length ? rows[0].domain : '',
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
      return { Modal: FakeModal, Spinner: FakeSpinner, Button: FakeButton };
    }
    if (request === '@wordpress/i18n') {
      return {
        __: (text: string) => text,
        sprintf: (format: string, ...args: string[]) =>
          format.replace('%s', args[0]),
      };
    }
    if (request === '@wordpress/element') {
      // Keep the real module for everything the component tree needs (forwardRef and friends),
      // and only flatten the interpolation so assertions can match on plain text.
      const actual = originalModuleLoad.apply(this, [
        request,
        parent,
        isMain,
      ]) as Record<string, unknown>;
      return { ...actual, createInterpolateElement: (text: string) => text };
    }
    if (request === 'mailpoet') {
      return {
        MailPoet: {
          apiVersion: 'v1',
          Ajax: {
            post: (options: AjaxCall) => {
              ajaxCalls.push(options);
              if (
                holdCreate &&
                options.action === 'createAuthorizedSenderDomain'
              ) {
                return new Promise(() => {
                  // never settles: the create is still in flight
                });
              }
              return Promise.resolve({
                data:
                  options.action === 'getAuthorizedSenderDomains'
                    ? recordsOnAccount
                    : recordsCreated,
              });
            },
          },
        },
      };
    }
    if (request === 'ajax') {
      return { isErrorResponse: () => false };
    }
    if (request === 'common/manage-sender-domain') {
      // Stand in for the two views so this spec stays about which requests the modal makes.
      // Loading the real tree drags @wordpress/components internals into the shared globals and
      // breaks the suites that run after this one.
      return {
        AddSenderDomain: FakeAddSenderDomain,
        ManageSenderDomain: FakeManageSenderDomain,
      };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

const renderModal = async (senderDomain: string) => {
  rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);
  await React.act(async () => {
    root.render(
      React.createElement(modalModule.AuthorizeSenderDomainModal, {
        senderDomain,
        onRequestClose: () => undefined,
        useModal: false,
      }),
    );
  });
};

const actionsCalled = () => ajaxCalls.map((call) => call.action);

const manageSenderDomain = () =>
  document.querySelector('[data-automation-id="manage_sender_domain"]');

const addDomainButton = () =>
  Array.from(document.querySelectorAll('button')).find(
    (button) => button.textContent === 'Add domain',
  );

describe('authorize sender domain modal', function authorizeSenderDomainModal() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    modalModule = testRequire(
      './assets/js/src/common/authorize-sender-domain-modal.tsx',
    ) as ModalModule;
    restoreModuleMocks();
  });

  beforeEach(() => {
    ajaxCalls = [];
    recordsOnAccount = [];
    recordsCreated = [];
    holdCreate = false;
  });

  afterEach(async () => {
    if (root) {
      await React.act(async () => {
        root.unmount();
      });
    }
    document.body.innerHTML = '';
    delete window.mailpoet_all_sender_domains;
  });

  after(() => {
    dom.window.close();
    restoreGlobals();
  });

  it('does not register a domain that is not on the account when it opens', async () => {
    window.mailpoet_all_sender_domains = [];

    await renderModal('example.com');

    expect(actionsCalled()).to.deep.equal(['getAuthorizedSenderDomains']);
    expect(addDomainButton()).to.not.equal(undefined);
  });

  it('registers the domain only once the button is clicked and shows its records', async () => {
    window.mailpoet_all_sender_domains = [];
    recordsCreated = [{ host: 'mailpoet1._domainkey.example.com' }];

    await renderModal('example.com');
    await React.act(async () => {
      addDomainButton().click();
      await Promise.resolve();
    });

    expect(actionsCalled()).to.deep.equal([
      'getAuthorizedSenderDomains',
      'createAuthorizedSenderDomain',
    ]);
    expect(ajaxCalls[1].data.domain).to.equal('example.com');
    expect(addDomainButton()).to.equal(undefined);
    expect(manageSenderDomain().getAttribute('data-hosts')).to.equal(
      'mailpoet1._domainkey.example.com',
    );
  });

  it('sends one create when Add domain is clicked twice while the first is in flight', async () => {
    window.mailpoet_all_sender_domains = [];
    holdCreate = true;

    await renderModal('example.com');
    await React.act(async () => {
      addDomainButton().click();
      await Promise.resolve();
    });
    await React.act(async () => {
      addDomainButton().click();
      await Promise.resolve();
    });

    expect(actionsCalled()).to.deep.equal([
      'getAuthorizedSenderDomains',
      'createAuthorizedSenderDomain',
    ]);
  });

  it('fetches the records for a domain already on the account', async () => {
    window.mailpoet_all_sender_domains = ['example.com'];
    recordsOnAccount = [{ host: 'mailpoet1._domainkey.example.com' }];

    await renderModal('example.com');

    expect(actionsCalled()).to.deep.equal(['getAuthorizedSenderDomains']);
    expect(addDomainButton()).to.equal(undefined);
  });

  // Most MailPoet pages show the "authenticate your sender domain" notice without localizing
  // mailpoet_all_sender_domains, and the list goes stale once a domain is added.
  it('shows the records, not the Add panel, for a domain on the account that the page does not list', async () => {
    recordsOnAccount = [{ host: 'mailpoet1._domainkey.example.com' }];

    await renderModal('example.com');

    expect(actionsCalled()).to.deep.equal(['getAuthorizedSenderDomains']);
    expect(addDomainButton()).to.equal(undefined);
    expect(manageSenderDomain().textContent).to.equal('example.com');
  });
});
