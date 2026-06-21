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

type TemplateEmailPreviewsModule = {
  TemplateEmailPreviews: React.ComponentType<{ templateSlug: string }>;
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
  onClick?: () => void;
};

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let previewsModule: TemplateEmailPreviewsModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot>;
let rootElement: HTMLDivElement;
let apiFetchStub: sinon.SinonStub;
let automationSteps: Record<string, Step>;

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

const fakeSelectors = {
  getAutomationData: () => ({ steps: automationSteps }),
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

function FakeEmailPreviewModal({
  srcDoc,
  onClose,
}: {
  srcDoc?: string;
  onClose: () => void;
}): JSX.Element {
  return React.createElement(
    'div',
    { role: 'dialog' },
    React.createElement('iframe', {
      'data-automation-id': 'template_email_preview_iframe',
      srcDoc,
      title: 'preview',
    }),
    React.createElement(
      'button',
      { onClick: onClose, type: 'button' },
      'Close',
    ),
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
    if (request === '@wordpress/data') {
      return {
        useSelect: (
          mapSelect: (select: () => typeof fakeSelectors) => unknown,
        ) => mapSelect(() => fakeSelectors),
      };
    }
    if (request === '@wordpress/i18n') {
      return {
        __: (text: string) => text,
        sprintf: (format: string, ...args: unknown[]) =>
          format.replace(/%d|%s/g, () => String(args.shift())),
      };
    }
    if (request === '@wordpress/api-fetch') {
      return (...args: unknown[]) => apiFetchStub(...args) as Promise<unknown>;
    }
    if (request === '@wordpress/url') {
      return {
        addQueryArgs: (path: string, args: Record<string, string>) => {
          const query = String(new dom.window.URLSearchParams(args));
          return query ? `${path}?${query}` : path;
        },
      };
    }
    if (request === '../../editor/store') {
      return { storeName: 'mailpoet-automation-editor' };
    }
    if (request === '../../components/email-preview-modal') {
      return { EmailPreviewModal: FakeEmailPreviewModal };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

const sendEmailStep = (id: string, args: Record<string, unknown>): Step => ({
  id,
  type: 'action',
  key: 'mailpoet:send-email',
  args,
  next_steps: [],
});

const render = async (templateSlug = 'welcome-series') => {
  rootElement = document.createElement('div');
  document.body.appendChild(rootElement);
  root = createRoot(rootElement);
  await React.act(async () => {
    root.render(
      React.createElement(previewsModule.TemplateEmailPreviews, {
        templateSlug,
      }),
    );
  });
};

const previewButtons = () =>
  Array.from(document.querySelectorAll('button')).filter(
    (button) => button.textContent === 'Preview',
  );

describe('automation template email previews', function templateEmailPreviews() {
  this.timeout(20000);

  before(async () => {
    setupWindow();
    installModuleMocks();
    previewsModule = testRequire(
      './assets/js/src/automation/templates/components/template-email-previews.tsx',
    ) as TemplateEmailPreviewsModule;
    restoreModuleMocks();
  });

  beforeEach(() => {
    apiFetchStub = sinon.stub();
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

  it('shows a preview button only for send email steps with a pattern', async () => {
    automationSteps = {
      'step-1': sendEmailStep('step-1', {
        name: 'Welcome',
        pattern: 'welcome-email-content',
      }),
      'step-2': sendEmailStep('step-2', { name: 'No pattern' }),
      'step-3': {
        id: 'step-3',
        type: 'trigger',
        key: 'mailpoet:someone-subscribes',
        args: { pattern: 'should-be-ignored' },
        next_steps: [],
      },
    };

    await render();

    expect(previewButtons().length).to.equal(1);
  });

  it('renders nothing when no email has a pattern', async () => {
    automationSteps = {
      'step-1': sendEmailStep('step-1', { name: 'No pattern' }),
    };

    await render();

    expect(rootElement.textContent).to.equal('');
    expect(previewButtons().length).to.equal(0);
  });

  it('fetches the rendered email and shows it in the modal', async () => {
    automationSteps = {
      'step-1': sendEmailStep('step-1', {
        name: 'Welcome',
        subject: 'Welcome subject',
        preheader: 'Preheader',
        pattern: 'welcome-email-content',
      }),
    };
    apiFetchStub.resolves({ data: { html: '<p>Preview body</p>' } });

    await render();

    await React.act(async () => {
      previewButtons()[0].dispatchEvent(
        new window.MouseEvent('click', { bubbles: true }),
      );
      await Promise.resolve();
      await Promise.resolve();
    });

    expect(apiFetchStub.calledOnce).to.equal(true);
    expect(apiFetchStub.firstCall.args[0].path).to.contain(
      'automation-template-email-preview',
    );
    expect(apiFetchStub.firstCall.args[0].path).to.contain(
      'pattern=welcome-email-content',
    );

    const iframe = document.querySelector(
      '[data-automation-id="template_email_preview_iframe"]',
    );
    expect(iframe).to.not.equal(null);
    expect(iframe.getAttribute('srcdoc')).to.equal('<p>Preview body</p>');
  });

  it('ignores a preview response after the template changed', async () => {
    automationSteps = {
      'step-1': sendEmailStep('step-1', {
        name: 'Welcome',
        pattern: 'welcome-email-content',
      }),
    };
    let resolvePreview: (value: unknown) => void = () => undefined;
    apiFetchStub.returns(
      new Promise((resolve) => {
        resolvePreview = resolve;
      }),
    );

    await render('template-a');

    await React.act(async () => {
      previewButtons()[0].dispatchEvent(
        new window.MouseEvent('click', { bubbles: true }),
      );
      await Promise.resolve();
    });

    // Switch to another template while the request is in flight.
    await React.act(async () => {
      root.render(
        React.createElement(previewsModule.TemplateEmailPreviews, {
          templateSlug: 'template-b',
        }),
      );
    });

    await React.act(async () => {
      resolvePreview({ data: { html: '<p>Stale</p>' } });
      await Promise.resolve();
      await Promise.resolve();
    });

    expect(
      document.querySelector(
        '[data-automation-id="template_email_preview_iframe"]',
      ),
    ).to.equal(null);
  });
});
