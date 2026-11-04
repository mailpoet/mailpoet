import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import React from 'react';
import { createRoot } from 'react-dom/client';

type NewSegment = {
  id: string;
  name: string;
  text: string;
  subscriberCount: number;
};
type OnCreateSegment = (segment: NewSegment) => void;
type SelectSegmentModule = {
  SelectSegment: React.ComponentType<{
    selectedSegments: string[];
    setSelectedSegments: (segments: string[]) => void;
  }>;
};
type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = {
  (path: string): unknown;
  cache: Record<string, unknown>;
  resolve: (path: string) => string;
};
type Select2Factory = (root: unknown, jQuery: unknown) => void;
type JQueryWithVal = (selector: string) => {
  val: (value: string[]) => { trigger: (event: string) => void };
};

const moduleLoadProperty = '_load';
const importDirectory =
  './assets/js/src/subscribers/import-export/import/step-data-manipulation';
const cachedModulePaths = [
  'jquery',
  `${importDirectory}/select-segment.jsx`,
  `${importDirectory}/generate-segment-selection.jsx`,
];
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

const existingSegments = [
  { id: '1', name: 'Newsletter mailing list', count: 3 },
  { id: '2', name: 'Customers', count: 5 },
];
const newSegment: NewSegment = {
  id: '3',
  name: 'Brand new list',
  text: 'Brand new list',
  subscriberCount: 0,
};

let dom: JSDOM;
let root: ReturnType<typeof createRoot>;
let originalModuleLoad: ModuleLoader;
let selectSegmentModule: SelectSegmentModule;
let jQuery: JQueryWithVal;
let onCreateSegment: OnCreateSegment;
let selectedSegments: string[];

const clearModuleCache = () => {
  cachedModulePaths.forEach((path) => {
    delete testRequire.cache[testRequire.resolve(path)];
  });
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
    if (request === 'mailpoet') {
      return {
        MailPoet: {
          I18n: { t: (key: string) => key },
          Notice: {
            error: (_message: string, { id }: { id: string }) => {
              const notice = document.createElement('div');
              notice.setAttribute('data-id', id);
              document.body.appendChild(notice);
            },
          },
        },
      };
    }
    if (request === 'context') {
      return {
        GlobalContext: React.createContext({
          segments: { all: existingSegments, updateAll: () => undefined },
        }),
      };
    }
    if (request === 'common/button/button') {
      return {
        Button: ({
          children,
          onClick,
        }: {
          children: React.ReactNode;
          onClick: () => void;
        }) =>
          React.createElement(
            'button',
            { type: 'button', className: 'create-list', onClick },
            children,
          ),
      };
    }
    if (request === './create-new-segment') {
      return {
        createNewSegment: (callback: OnCreateSegment) => {
          onCreateSegment = callback;
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

function SelectSegmentStep() {
  const [selected, setSelected] = React.useState<string[]>([]);
  selectedSegments = selected;
  return React.createElement(selectSegmentModule.SelectSegment, {
    selectedSegments: selected,
    setSelectedSegments: setSelected,
  });
}

const setPickedSegments = async (ids: string[]) => {
  await React.act(async () => {
    jQuery('#mailpoet_segments_select').val(ids).trigger('change');
  });
};

const openCreateListModal = async () => {
  await React.act(async () => {
    document.querySelector<HTMLElement>('.create-list').click();
  });
};

const finishCreatingList = async () => {
  await React.act(async () => {
    onCreateSegment(newSegment);
  });
};

const createList = async () => {
  await openCreateListModal();
  await finishCreatingList();
};

const getSelectedNames = () =>
  Array.from(
    document.querySelectorAll(
      '.select2-selection__choice .mailpoet-form-select2-text',
    ),
  ).map((name) => name.textContent);

const getRequiredListNotice = () =>
  document.querySelector('[data-id="notice_segmentSelection"]');

describe('Subscriber import list selection', function selectSegmentSuite() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    clearModuleCache();
    jQuery = testRequire('jquery') as JQueryWithVal;
    (testRequire('select2') as Select2Factory)(dom.window, jQuery);
    installModuleMocks();
    selectSegmentModule = testRequire(
      `${importDirectory}/select-segment.jsx`,
    ) as SelectSegmentModule;
    restoreModuleMocks();
  });

  after(() => {
    clearModuleCache();
    dom.window.close();
    delete (global as { document?: unknown }).document;
    delete (global as { window?: unknown }).window;
    delete (global as { Element?: unknown }).Element;
    delete (global as { HTMLElement?: unknown }).HTMLElement;
    delete (global as { Node?: unknown }).Node;
    delete (global as { navigator?: unknown }).navigator;
  });

  beforeEach(async () => {
    const rootElement = document.createElement('div');
    document.body.appendChild(rootElement);
    root = createRoot(rootElement);
    await React.act(async () => {
      root.render(React.createElement(SelectSegmentStep));
    });
  });

  afterEach(async () => {
    await React.act(async () => {
      root.unmount();
    });
    document.body.innerHTML = '';
  });

  it('keeps a list picked while the new list was being created', async () => {
    await openCreateListModal();
    await setPickedSegments(['2']);

    await finishCreatingList();

    expect(getSelectedNames()).to.deep.equal(['Customers', 'Brand new list']);
    expect(selectedSegments).to.deep.equal(['2', '3']);
  });

  it('does not ask for a list when the step opens', () => {
    expect(getRequiredListNotice()).to.equal(null);
  });

  it('removes the required list notice when a new list is created', async () => {
    await setPickedSegments([]);
    expect(getRequiredListNotice()).to.not.equal(null);

    await createList();

    expect(getRequiredListNotice()).to.equal(null);
  });
});
