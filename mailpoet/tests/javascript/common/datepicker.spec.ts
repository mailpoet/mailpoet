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
type DatepickerModule = {
  Datepicker: (props: Record<string, unknown>) => JSX.Element;
};

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let datepickerModule: DatepickerModule;
let root: Root;
let createElement: typeof import('react').createElement;
let useState: typeof import('react').useState;
let createRoot: typeof import('react-dom/client').createRoot;
let act: (callback: () => unknown) => void;

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
};

const day = (dayOfMonth: number) =>
  Array.from(
    document.querySelectorAll<HTMLElement>(
      '.react-datepicker__day:not(.react-datepicker__day--outside-month)',
    ),
  ).find((element) => element.textContent === String(dayOfMonth));

const pressKey = (target: Element, key: string) =>
  act(() => {
    target.dispatchEvent(
      new dom.window.KeyboardEvent('keydown', {
        key,
        bubbles: true,
      }) as KeyboardEvent,
    );
  });

// Mirrors how DateText uses the picker: a controlled value rebuilt on each
// render and a minimum date equal to the start of the current day.
function Harness() {
  const [value, setValue] = useState('2026-10-24');
  const { Datepicker } = datepickerModule;
  return createElement(Datepicker, {
    selected: new Date(`${value}T00:00:00`),
    minDate: new Date('2026-10-23T00:00:00'),
    onChange: (date: Date) =>
      setValue(
        `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()}`,
      ),
    dateFormat: 'yyyy-MM-dd',
    locale: 'en',
  });
}

describe('Datepicker keyboard navigation', function datepickerSuite() {
  this.timeout(20000);

  before(() => {
    setupWindow();
    // react-dom decides at load time whether a DOM exists; reload it if an
    // earlier spec loaded it without one.
    Object.keys(testRequire.cache).forEach((file) => {
      if (/[\\/]react-dom[\\/]/.test(file)) {
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
        return { MailPoet: { wpWeekStartsOn: 1 } };
      }
      return originalLoad.apply(this, [request, parent, isMain]);
    };
    ({ createElement, useState } = testRequire(
      'react',
    ) as typeof import('react'));
    ({ createRoot } = testRequire(
      'react-dom/client',
    ) as typeof import('react-dom/client'));
    ({ act } = testRequire('react') as { act: typeof act });
    datepickerModule = testRequire(
      './assets/js/src/common/datepicker/datepicker.tsx',
    ) as DatepickerModule;
    moduleWithLoader[moduleLoadProperty] = originalLoad;
  });

  beforeEach(() => {
    root = createRoot(document.getElementById('root'));
    act(() => root.render(createElement(Harness)));
    const input = document.querySelector('input');
    act(() => {
      input.focus();
      input.click();
    });
    act(() => day(24).focus());
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
  });

  it('moves focus to the previous allowed day with ArrowLeft', () => {
    pressKey(day(24), 'ArrowLeft');
    expect(document.activeElement.textContent).to.equal('23');
  });

  it('moves focus to the next day with ArrowRight', () => {
    pressKey(day(24), 'ArrowRight');
    expect(document.activeElement.textContent).to.equal('25');
  });

  it('moves focus a week down with ArrowDown', () => {
    pressKey(day(24), 'ArrowDown');
    expect(document.activeElement.textContent).to.equal('31');
  });
});
