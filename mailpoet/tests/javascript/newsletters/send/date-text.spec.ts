import { JSDOM } from 'jsdom';
import NodeModule from 'module';
import type { Root } from 'react-dom/client';

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = {
  (path: string): unknown;
  cache: Record<string, unknown>;
};
type ChangeEvent = { target: { name?: string; value?: string } };
type DateTextModule = {
  DateText: (props: Record<string, unknown>) => JSX.Element;
};

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let dateTextModule: DateTextModule;
let root: Root;
let createElement: typeof import('react').createElement;
let createRoot: typeof import('react-dom/client').createRoot;
let act: (callback: () => unknown) => void;
let calls: ChangeEvent[];

const setupWindow = () => {
  dom = new JSDOM(
    '<!doctype html><html><body><div id="root"></div></body></html>',
    { url: 'https://example.com/wp-admin/admin.php', pretendToBeVisual: true },
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
  global.HTMLInputElement = dom.window.HTMLInputElement;
  global.requestAnimationFrame = dom.window.requestAnimationFrame;
  global.cancelAnimationFrame = dom.window.cancelAnimationFrame;
  (window as unknown as Record<string, unknown>).mailpoet_current_date =
    '2026-10-23 10:00:00';
};

const day = (dayOfMonth: number) =>
  Array.from(
    document.querySelectorAll<HTMLElement>(
      '.react-datepicker__day:not(.react-datepicker__day--outside-month)',
    ),
  ).find((element) => element.textContent === String(dayOfMonth));

describe('Schedule DateText', function dateTextSuite() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    // react-dom decides at load time whether a DOM exists; reload it if an
    // earlier spec loaded it without one.
    Object.keys(testRequire.cache).forEach((file) => {
      if (
        /[\\/]react-dom[\\/]/.test(file) ||
        /[\\/](date-text|datepicker)[\\/.]/.test(file)
      ) {
        delete testRequire.cache[file];
      }
    });
    (
      global as { IS_REACT_ACT_ENVIRONMENT?: boolean }
    ).IS_REACT_ACT_ENVIRONMENT = true;
    const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
    const originalLoad = moduleWithLoader[moduleLoadProperty];
    moduleWithLoader[moduleLoadProperty] = function loadModule(
      request: string,
      parent: unknown,
      isMain: boolean,
    ) {
      if (request === 'mailpoet') {
        return {
          MailPoet: {
            wpWeekStartsOn: 1,
            Date: { convertFormat: () => 'YYYY-MM-DD' },
          },
        };
      }
      if (request === '@wordpress/i18n') {
        return { __: (text: string) => text, _x: (text: string) => text };
      }
      return originalLoad.apply(this, [request, parent, isMain]);
    };
    ({ createElement } = testRequire('react') as typeof import('react'));
    ({ createRoot } = testRequire(
      'react-dom/client',
    ) as typeof import('react-dom/client'));
    ({ act } = testRequire('react') as { act: typeof act });
    dateTextModule = testRequire(
      './assets/js/src/newsletters/send/date-text.tsx',
    ) as DateTextModule;
    moduleWithLoader[moduleLoadProperty] = originalLoad;
  });

  beforeEach(() => {
    calls = [];
    root = createRoot(document.getElementById('root'));
    act(() =>
      root.render(
        createElement(dateTextModule.DateText, {
          name: 'date',
          value: '2026-10-24',
          displayFormat: 'F j, Y',
          storageFormat: 'Y-m-d',
          disabled: false,
          validation: {},
          maxDate: new Date('2027-10-23T00:00:00'),
          onChange: (event: ChangeEvent) =>
            calls.push({
              target: { name: event.target.name, value: event.target.value },
            }),
        }),
      ),
    );
  });

  afterEach(() => {
    act(() => root.unmount());
  });

  after(() => {
    dom.window.close();
    delete (global as { document?: unknown }).document;
    delete (global as { window?: unknown }).window;
    delete (global as { Element?: unknown }).Element;
    delete (global as { Node?: unknown }).Node;
    delete (global as { HTMLElement?: unknown }).HTMLElement;
    delete (global as { HTMLInputElement?: unknown }).HTMLInputElement;
    delete (global as { requestAnimationFrame?: unknown })
      .requestAnimationFrame;
    delete (global as { cancelAnimationFrame?: unknown }).cancelAnimationFrame;
  });

  it('reports a picked day in the storage format', () => {
    const input = document.querySelector('input');
    act(() => {
      input.focus();
      input.click();
    });
    act(() => day(25).click());
    expect(calls).to.have.length(1);
    expect(calls[0].target.name).to.equal('date');
    expect(calls[0].target.value).to.equal('2026-10-25');
  });

  it('ignores a cleared date', () => {
    const input = document.querySelector('input');
    const { set: setValue } = Object.getOwnPropertyDescriptor(
      dom.window.HTMLInputElement.prototype,
      'value',
    ) as { set: (this: HTMLInputElement, value: string) => void };
    act(() => {
      input.focus();
      setValue.call(input, '');
      input.dispatchEvent(
        new dom.window.Event('input', { bubbles: true }) as Event,
      );
    });
    expect(calls.map((call) => call.target.value)).to.not.include(
      'Invalid date',
    );
    expect(calls).to.have.length(0);
  });
});
