import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';
import sinon from 'sinon';

type ConfirmationEmailItem = {
  id?: number;
  confirmation_email_id?: number;
};

type OnValueChangeEvent = { target: { name: string; value: string } };

type FormModule = {
  ConfirmationEmailField: React.ComponentType<{
    onValueChange: (event: OnValueChangeEvent) => void;
    item: ConfirmationEmailItem;
  }>;
};

type ConfirmAlertProps = {
  title?: string;
  message?: string;
  confirmLabel?: string;
  returnFocus?: () => Element | null;
  onConfirm: () => unknown;
};

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = ((path: string) => unknown) & {
  resolve: (path: string) => string;
};

const FORM_MODULE_PATH = './assets/js/src/segments/static/form.tsx';
const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let originalModuleLoad: ModuleLoader;
let formModule: FormModule;
let root: ReturnType<typeof createRoot>;
let rootElement: HTMLDivElement;

let ajaxPostStub: sinon.SinonStub;
let noticeSuccessStub: sinon.SinonStub;
let noticeErrorStub: sinon.SinonStub;
let onValueChangeStub: sinon.SinonStub;
let lastConfirmAlertProps: ConfirmAlertProps | null;

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

const fakeMailPoet = {
  apiVersion: 'v1',
  I18n: { t: (key: string) => key },
  trackEvent: () => undefined,
  Ajax: {
    post: (...args: unknown[]): Promise<unknown> =>
      ajaxPostStub(...args) as Promise<unknown>,
  },
  Notice: {
    success: (...args: unknown[]): void => {
      noticeSuccessStub(...args);
    },
    showApiErrorNotice: (...args: unknown[]): void => {
      noticeErrorStub(...args);
    },
  },
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
      return {
        __: (text: string) => text,
        sprintf: (format: string, ...args: unknown[]) =>
          args.reduce(
            (acc: string, arg) => acc.replace('%s', String(arg)),
            format,
          ),
      };
    }
    if (request === 'mailpoet') {
      return { MailPoet: fakeMailPoet };
    }
    if (request === 'common/confirm-alert.jsx') {
      return {
        confirmAlert: (props: ConfirmAlertProps) => {
          lastConfirmAlertProps = props;
        },
      };
    }
    if (request === 'form/form.jsx') {
      return { Form: () => null };
    }
    if (request === 'notices/subscribers-limit-notice') {
      return { SubscribersLimitNotice: () => null };
    }
    if (request === 'react-router-dom') {
      return { useParams: () => ({}) };
    }
    if (request === '../../common/page-header') {
      return { BackButton: () => null, PageHeader: () => null };
    }
    if (request === '../../common/top-bar/top-bar') {
      return { TopBarWithBoundary: () => null };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

// The module under test keeps its confirmation-email list at module scope
// (it's shared across the lists SPA's remounts), so each test re-requires a
// fresh copy of the module with its own window globals to stay isolated from
// the others. Test 5 is the exception: it deliberately reuses one module
// load across two mounts to exercise that sharing.
const loadFreshFormModule = (
  options: {
    emails?: Array<{ id: number; subject: string }>;
    defaultId?: number;
  } = {},
) => {
  window.mailpoet_confirmation_emails = options.emails
    ? [...options.emails]
    : [];
  window.mailpoet_default_confirmation_email_id = options.defaultId;
  window.mailpoet_pages = [];

  const resolvedPath = testRequire.resolve(FORM_MODULE_PATH);
  const cache = (testRequire as unknown as { cache: Record<string, unknown> })
    .cache;
  delete cache[resolvedPath];

  installModuleMocks();
  formModule = testRequire(FORM_MODULE_PATH) as FormModule;
  restoreModuleMocks();
};

const render = async (item: ConfirmationEmailItem) => {
  rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);
  await React.act(async () => {
    root.render(
      React.createElement(formModule.ConfirmationEmailField, {
        onValueChange: onValueChangeStub,
        item,
      }),
    );
  });
};

const unmount = async () => {
  if (root) {
    await React.act(async () => {
      root.unmount();
    });
    root = undefined;
  }
  rootElement.remove();
};

const deleteButton = () =>
  document.querySelector('[data-automation-id="delete_confirmation_email"]');

const clickDelete = async () => {
  await React.act(async () => {
    deleteButton().dispatchEvent(
      new window.MouseEvent('click', { bubbles: true }),
    );
  });
};

const confirmDelete = async () => {
  await React.act(async () => {
    await lastConfirmAlertProps.onConfirm();
  });
};

const selectOptionValues = () =>
  Array.from(document.querySelectorAll('select option')).map(
    (option) => (option as HTMLOptionElement).value,
  );

describe('ConfirmationEmailField', function confirmationEmailFieldSuite() {
  this.timeout(20000);

  before(() => {
    setupWindow();
  });

  beforeEach(() => {
    ajaxPostStub = sinon.stub();
    noticeSuccessStub = sinon.stub();
    noticeErrorStub = sinon.stub();
    onValueChangeStub = sinon.stub();
    lastConfirmAlertProps = null;
  });

  afterEach(async () => {
    await unmount();
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

  it('hides the delete button for the global default and shows it for a custom email', async () => {
    loadFreshFormModule({
      emails: [
        { id: 5, subject: 'Default email' },
        { id: 7, subject: 'Custom email' },
      ],
      defaultId: 5,
    });

    await render({ id: 10, confirmation_email_id: 0 });
    expect(deleteButton()).to.equal(null);
    await unmount();

    await render({ id: 10, confirmation_email_id: 5 });
    expect(deleteButton()).to.equal(null);
    await unmount();

    await render({ id: 10, confirmation_email_id: 7 });
    expect(deleteButton()).to.not.equal(null);
  });

  it('deletes the saved email and resets the field to the global default', async () => {
    loadFreshFormModule({
      emails: [{ id: 7, subject: 'Custom email' }],
      defaultId: 5,
    });
    ajaxPostStub.resolves({});

    await render({ id: 10, confirmation_email_id: 7 });
    await clickDelete();
    expect(lastConfirmAlertProps).to.not.equal(null);

    await confirmDelete();

    expect(ajaxPostStub.calledOnce).to.equal(true);
    expect(ajaxPostStub.firstCall.args[0]).to.deep.include({
      endpoint: 'newsletters',
      action: 'deleteConfirmationEmail',
    });
    expect(ajaxPostStub.firstCall.args[0].data).to.deep.equal({ id: '7' });

    expect(selectOptionValues()).to.not.include('7');
    expect(onValueChangeStub.calledOnce).to.equal(true);
    expect(onValueChangeStub.firstCall.args[0]).to.deep.equal({
      target: { name: 'confirmation_email_id', value: '0' },
    });
    expect(noticeSuccessStub.calledOnce).to.equal(true);
  });

  it('deletes a newly selected email and restores the value to the still-existing saved email', async () => {
    loadFreshFormModule({
      emails: [
        { id: 5, subject: 'Saved email' },
        { id: 8, subject: 'Newly selected email' },
      ],
      defaultId: undefined,
    });
    ajaxPostStub.resolves({});

    // Mount with the saved email (5), then move the selection to a
    // different email (8) without changing item.id, as the parent form
    // would when the user picks a different option from the dropdown.
    await render({ id: 10, confirmation_email_id: 5 });
    await React.act(async () => {
      root.render(
        React.createElement(formModule.ConfirmationEmailField, {
          onValueChange: onValueChangeStub,
          item: { id: 10, confirmation_email_id: 8 },
        }),
      );
    });

    await clickDelete();
    await confirmDelete();

    expect(ajaxPostStub.firstCall.args[0].data).to.deep.equal({ id: '8' });
    expect(onValueChangeStub.calledOnce).to.equal(true);
    expect(onValueChangeStub.firstCall.args[0]).to.deep.equal({
      target: { name: 'confirmation_email_id', value: '5' },
    });
  });

  it('shows an error notice and keeps the option when the delete request fails', async () => {
    loadFreshFormModule({
      emails: [{ id: 7, subject: 'Custom email' }],
      defaultId: undefined,
    });
    ajaxPostStub.rejects(new Error('failed'));

    await render({ id: 10, confirmation_email_id: 7 });
    await clickDelete();
    await confirmDelete();

    expect(noticeErrorStub.calledOnce).to.equal(true);
    expect(selectOptionValues()).to.include('7');
    expect(onValueChangeStub.called).to.equal(false);
  });

  it('does not show a deleted option in a freshly mounted field for another list', async () => {
    loadFreshFormModule({
      emails: [{ id: 7, subject: 'Custom email' }],
      defaultId: undefined,
    });
    ajaxPostStub.resolves({});

    await render({ id: 10, confirmation_email_id: 7 });
    await clickDelete();
    await confirmDelete();
    expect(selectOptionValues()).to.not.include('7');
    await unmount();

    // Simulate opening a different list: a fresh mount of the same module
    // instance (the lists page is a HashRouter SPA that remounts the field).
    await render({ id: 20, confirmation_email_id: 0 });
    expect(selectOptionValues()).to.not.include('7');
  });
});
