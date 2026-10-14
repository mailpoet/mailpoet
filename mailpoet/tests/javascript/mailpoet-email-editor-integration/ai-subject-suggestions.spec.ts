import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

type Suggestion = { subject: string; preheader: string };

type SuggestionsModule = {
  AiSubjectSuggestions: React.ComponentType<{
    onSelect: (suggestion: Suggestion) => void;
  }>;
};

type PendingRequest = {
  resolve: (value: unknown) => void;
  reject: (error: unknown) => void;
};

type TrackedEvent = { name: string; data: Record<string, unknown> };

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

const suggestions: Suggestion[] = [
  { subject: 'Subject one', preheader: 'Preheader one' },
  { subject: 'Subject two', preheader: 'Preheader two' },
  { subject: 'Subject three', preheader: 'Preheader three' },
  { subject: 'Subject four', preheader: 'Preheader four' },
];

let dom: JSDOM;
let suggestionsModule: SuggestionsModule;
let originalModuleLoad: ModuleLoader;
let root: ReturnType<typeof createRoot> | null;
let requests: PendingRequest[];
let trackedEvents: TrackedEvent[];
let selected: Suggestion[];

const setupWindow = () => {
  dom = new JSDOM('<!doctype html><html><body></body></html>', {
    url: 'https://example.com/wp-admin/post.php',
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
  window.mailpoet_ai_text_generation_available = true;
};

function FakeButton({
  children,
  onClick,
  className,
  label,
}: {
  children?: React.ReactNode;
  onClick?: () => void;
  className?: string;
  label?: string;
}): JSX.Element {
  return React.createElement(
    'button',
    { type: 'button', onClick, className, 'aria-label': label },
    children,
  );
}

function FakeDropdown({
  renderToggle,
  renderContent,
}: {
  renderToggle: (props: {
    isOpen: boolean;
    onToggle: () => void;
  }) => React.ReactNode;
  renderContent: (props: { onClose: () => void }) => React.ReactNode;
}): JSX.Element {
  const [isOpen, setIsOpen] = React.useState(false);
  return React.createElement(
    'div',
    null,
    renderToggle({ isOpen, onToggle: () => setIsOpen((open) => !open) }),
    isOpen ? renderContent({ onClose: () => setIsOpen(false) }) : null,
  );
}

function FakeContainer({
  children,
}: {
  children?: React.ReactNode;
}): JSX.Element {
  return React.createElement('div', null, children);
}

const fakeApiFetch = ({ signal }: { signal: AbortSignal }) =>
  new Promise((resolve, reject) => {
    requests.push({ resolve, reject });
    signal.addEventListener('abort', () => {
      const error = new Error('The operation was aborted.');
      error.name = 'AbortError';
      reject(error);
    });
  });

const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === '@wordpress/components') {
      return {
        Button: FakeButton,
        Dropdown: FakeDropdown,
        Notice: FakeContainer,
        Spinner: FakeContainer,
        __experimentalVStack: FakeContainer,
        __experimentalHStack: FakeContainer,
        __experimentalHeading: FakeContainer,
        __experimentalSpacer: FakeContainer,
      };
    }
    if (request === '@wordpress/api-fetch') {
      return { __esModule: true, default: fakeApiFetch };
    }
    if (request === '@wordpress/data') {
      return { select: () => ({ getCurrentPostId: () => 123 }) };
    }
    if (request === '@wordpress/editor') {
      return { store: {} };
    }
    if (request === '@wordpress/icons') {
      return { closeSmall: null };
    }
    if (request === '@wordpress/i18n') {
      return { __: (text: string) => text };
    }
    if (request === 'mailpoet') {
      return {
        MailPoet: {
          trackEvent: (name: string, data: Record<string, unknown>) => {
            trackedEvents.push({ name, data });
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
      React.createElement(suggestionsModule.AiSubjectSuggestions, {
        onSelect: (suggestion: Suggestion) => selected.push(suggestion),
      }),
    );
  });
};

const click = async (element: Element) => {
  await React.act(async () => {
    (element as HTMLElement).click();
    await Promise.resolve();
  });
};

const toggleButton = () =>
  document.querySelector('.mailpoet-ai-suggestions__toggle');

const buttonWithText = (text: string) =>
  Array.from(document.querySelectorAll('button')).find(
    (button) => button.textContent === text,
  );

const settle = async (callback: () => void) => {
  await React.act(async () => {
    callback();
    await new Promise((resolve) => {
      setTimeout(resolve, 0);
    });
  });
};

const eventsNamed = (name: string) =>
  trackedEvents.filter((event) => event.name === name);

describe('AI subject suggestions tracking', function aiSuggestionsTracking() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    installModuleMocks();
    suggestionsModule = testRequire(
      './assets/js/src/mailpoet-email-editor-integration/sidebar/components/ai-subject-suggestions.tsx',
    ) as SuggestionsModule;
    restoreModuleMocks();
  });

  beforeEach(() => {
    requests = [];
    trackedEvents = [];
    selected = [];
  });

  afterEach(async () => {
    if (root) {
      await React.act(async () => {
        root.unmount();
      });
      root = null;
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
    ].forEach((property) => Reflect.deleteProperty(global, property));
  });

  it('tracks the request and the shown suggestions', async () => {
    await render();
    await click(toggleButton());

    expect(trackedEvents).to.deep.equal([
      { name: 'Emails > AI suggestions requested', data: { retry: false } },
    ]);

    await settle(() => requests[0].resolve({ data: { suggestions } }));

    const shown = eventsNamed('Emails > AI suggestions shown');
    expect(shown).to.have.length(1);
    expect(shown[0].data.count).to.equal(4);
    expect(shown[0].data.duration_ms).to.be.a('number');
    expect(shown[0].data.duration_ms).to.be.at.least(0);
  });

  it('tracks the position of the selected suggestion without its text', async () => {
    await render();
    await click(toggleButton());
    await settle(() => requests[0].resolve({ data: { suggestions } }));

    await click(document.querySelectorAll('.mailpoet-ai-suggestions__item')[1]);

    expect(selected).to.deep.equal([suggestions[1]]);
    expect(eventsNamed('Emails > AI suggestion selected')).to.deep.equal([
      { name: 'Emails > AI suggestion selected', data: { position: 2 } },
    ]);
    const payloads = JSON.stringify(trackedEvents);
    suggestions.forEach(({ subject, preheader }) => {
      expect(payloads).to.not.include(subject);
      expect(payloads).to.not.include(preheader);
    });
  });

  it('tracks a failed request with its error code and a retry', async () => {
    await render();
    await click(toggleButton());
    await settle(() =>
      requests[0].reject({
        code: 'mailpoet_ai_generation_failed',
        message: 'Failed to generate suggestions. Please try again.',
      }),
    );

    expect(eventsNamed('Emails > AI suggestions failed')).to.deep.equal([
      {
        name: 'Emails > AI suggestions failed',
        data: { error_code: 'mailpoet_ai_generation_failed' },
      },
    ]);

    await click(buttonWithText('Try again'));

    expect(eventsNamed('Emails > AI suggestions requested')).to.deep.equal([
      { name: 'Emails > AI suggestions requested', data: { retry: false } },
      { name: 'Emails > AI suggestions requested', data: { retry: true } },
    ]);
  });

  it('does not track the outcome of an aborted request', async () => {
    await render();
    await click(toggleButton());
    // Closing and reopening the dropdown starts a new request and aborts the first one.
    await click(toggleButton());
    await click(toggleButton());
    await settle(() => requests[1].resolve({ data: { suggestions } }));
    // A late answer to the aborted request must not be tracked either.
    await settle(() => requests[0].resolve({ data: { suggestions } }));

    expect(eventsNamed('Emails > AI suggestions requested')).to.have.length(2);
    expect(eventsNamed('Emails > AI suggestions shown')).to.have.length(1);
    expect(eventsNamed('Emails > AI suggestions failed')).to.have.length(0);
  });

  it('does not track a request aborted by unmounting', async () => {
    await render();
    await click(toggleButton());
    await React.act(async () => {
      root.unmount();
    });
    root = null;
    await settle(() => undefined);

    expect(trackedEvents.map((event) => event.name)).to.deep.equal([
      'Emails > AI suggestions requested',
    ]);
  });
});
