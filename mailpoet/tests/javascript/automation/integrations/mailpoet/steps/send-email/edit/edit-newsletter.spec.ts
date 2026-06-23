import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';
import sinon from 'sinon';

type Step = {
  id: string;
  type: string;
  key: string;
  args: Record<string, unknown>;
  next_steps: unknown[];
};

type EditNewsletterModule = {
  EditNewsletter: React.ComponentType;
};

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;

type ModuleWithLoader = Record<string, ModuleLoader>;
type FakeButtonProps = {
  children?: React.ReactNode;
  disabled?: boolean;
  onClick?: () => void;
};
type FakeModalProps = {
  children?: React.ReactNode;
  onRequestClose?: () => void;
  title?: string;
};
type SelectMapper = (select: () => ReturnType<typeof getSelectors>) => unknown;
type TestRequire = (path: string) => unknown;

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);
const noticesStore = { name: 'notices' };
let createErrorNoticeStub: sinon.SinonStub;
let dom: JSDOM;
let editNewsletter: EditNewsletterModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;
let rootElement: HTMLDivElement;
let selectedStep: Step;
let ajaxPostStub: sinon.SinonStub;
let windowOpenStub: sinon.SinonStub;

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

  Object.assign(window, {
    mailpoet_api_version: 'v1',
    mailpoet_feature_flags: {},
  });
};

const getSelectors = () => ({
  getAutomationData: () => ({ id: 12 }),
  getContext: (key: string) =>
    key === 'mailpoet' ? { block_email_editor_enabled: true } : undefined,
  getSelectedStep: () => selectedStep,
  getStepError: () => undefined,
});

const fakeMailPoet = {
  Ajax: {
    post: (...args: unknown[]): Promise<unknown> =>
      ajaxPostStub(...args) as Promise<unknown>,
  },
  getBlockEmailEditorUrl: (postId: number) =>
    `post.php?post=${postId}&action=edit`,
  getNewsletterEditorUrl: (newsletterId: number, context: string) =>
    `admin.php?page=mailpoet-newsletter-editor&id=${newsletterId}&context=${context}`,
};

function FakeButton({
  children,
  disabled = false,
  onClick = undefined,
}: FakeButtonProps): JSX.Element {
  return React.createElement(
    'button',
    { disabled, onClick, type: 'button' },
    children,
  );
}

function FakeModal({
  children,
  onRequestClose = undefined,
  title = '',
}: FakeModalProps): JSX.Element {
  return React.createElement(
    'div',
    { role: 'dialog' },
    React.createElement('h1', {}, title),
    React.createElement(
      'button',
      { onClick: onRequestClose, type: 'button' },
      'Close',
    ),
    children,
  );
}

function FakeSpinner(): JSX.Element {
  return React.createElement('span', {
    'data-automation-id': 'automation_send_email_preview_spinner',
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
      return { Button: FakeButton, Modal: FakeModal, Spinner: FakeSpinner };
    }

    if (request === '@wordpress/data') {
      return {
        dispatch: (store: unknown) =>
          store === noticesStore
            ? { createErrorNotice: createErrorNoticeStub }
            : {
                save: () => Promise.resolve({ saved: true }),
                updateStepArgs: () => undefined,
              },
        select: () => getSelectors(),
        useSelect: (mapSelect: SelectMapper) => mapSelect(() => getSelectors()),
      };
    }

    if (request === '@wordpress/i18n') {
      return { __: (text: string) => text };
    }

    if (request === '@wordpress/icons') {
      return { plus: 'plus' };
    }

    if (request === '@wordpress/notices') {
      return { store: noticesStore };
    }

    if (request === '../../../../../../mailpoet') {
      return { MailPoet: fakeMailPoet };
    }

    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

const loadModules = async () => {
  editNewsletter = testRequire(
    './assets/js/src/automation/integrations/mailpoet/steps/send-email/edit/edit-newsletter.tsx',
  ) as EditNewsletterModule;
};

const setSelectedStep = (emailId: number) => {
  selectedStep = {
    id: 'step-1',
    type: 'action',
    key: 'mailpoet:send-email',
    args: {
      email_id: emailId,
      email_wp_post_id: 456,
    },
    next_steps: [],
  };
};

const renderEditNewsletter = async () => {
  rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);

  await React.act(async () => {
    root.render(React.createElement(editNewsletter.EditNewsletter));
  });
};

const clickPreview = async () => {
  const previewButton = Array.from(document.querySelectorAll('button')).find(
    (button) => button.textContent === 'Preview',
  );
  expect(previewButton).to.not.equal(undefined);

  await React.act(async () => {
    previewButton.dispatchEvent(
      new window.MouseEvent('click', {
        bubbles: true,
      }),
    );
    await Promise.resolve();
    await Promise.resolve();
  });
};

describe('automation send email editor preview', function automationSendEmailEditorPreview() {
  this.timeout(20000);

  before(async () => {
    setupWindow();
    installModuleMocks();
    await loadModules();
    restoreModuleMocks();
  });

  beforeEach(() => {
    createErrorNoticeStub = sinon.stub();
    ajaxPostStub = sinon.stub();
    windowOpenStub = sinon.stub(window, 'open');
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

  it('opens block-editor automation email previews in a modal iframe', async () => {
    setSelectedStep(123);
    ajaxPostStub.resolves({ meta: { preview_url: '/mailpoet-preview' } });
    await renderEditNewsletter();

    await clickPreview();

    const iframe = document.querySelector(
      '[data-automation-id="automation_send_email_preview_iframe"]',
    );
    expect(iframe).to.not.equal(null);
    expect(iframe.getAttribute('src')).to.equal('/mailpoet-preview');
    expect(ajaxPostStub.calledOnce).to.equal(true);
    expect(ajaxPostStub.firstCall.args[0]).to.deep.include({
      action: 'get',
      endpoint: 'newsletters',
    });
    expect(ajaxPostStub.firstCall.args[0].data).to.deep.equal({ id: 123 });
    expect(windowOpenStub.notCalled).to.equal(true);
  });

  it('re-enables the preview button when the email changes mid-request', async () => {
    setSelectedStep(555);
    let resolvePreview: (value: unknown) => void = () => undefined;
    ajaxPostStub.returns(
      new Promise((resolve) => {
        resolvePreview = resolve;
      }),
    );
    await renderEditNewsletter();

    await clickPreview();

    const findPreviewButton = () =>
      Array.from(document.querySelectorAll('button')).find(
        (button) => button.textContent === 'Preview',
      );
    expect(findPreviewButton().disabled).to.equal(true);

    setSelectedStep(999);
    await React.act(async () => {
      root.render(React.createElement(editNewsletter.EditNewsletter));
    });

    await React.act(async () => {
      resolvePreview({ meta: { preview_url: '/mailpoet-preview' } });
      await Promise.resolve();
      await Promise.resolve();
    });

    expect(findPreviewButton().disabled).to.equal(false);
    expect(
      document.querySelector(
        '[data-automation-id="automation_send_email_preview_iframe"]',
      ),
    ).to.equal(null);
  });

  it('shows an error notice when the preview URL is missing', async () => {
    setSelectedStep(789);
    ajaxPostStub.resolves({ meta: {} });
    await renderEditNewsletter();

    await clickPreview();

    expect(
      document.querySelector(
        '[data-automation-id="automation_send_email_preview_iframe"]',
      ),
    ).to.equal(null);
    expect(createErrorNoticeStub.calledOnce).to.equal(true);
    expect(createErrorNoticeStub.firstCall.args[0]).to.equal(
      'MailPoet could not open the email preview. Please make sure the email still exists and try again.',
    );
  });
});
