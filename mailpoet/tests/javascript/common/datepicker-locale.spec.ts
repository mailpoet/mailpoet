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
type EnUS = {
  options: { weekStartsOn: number };
  localize: { month: (index: number) => string };
};

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let dom: JSDOM;
let root: Root;
let enUS: EnUS;
let Datepicker: (props: Record<string, unknown>) => JSX.Element;
let restoreLoader: () => void;
let loadDateText: () => unknown;
let createElement: typeof import('react').createElement;
let createRoot: typeof import('react-dom/client').createRoot;
let act: (callback: () => unknown) => void;

describe('Datepicker locale', function datepickerLocaleSuite() {
  this.timeout(20000);

  before(() => {
    dom = new JSDOM(
      '<!doctype html><html><body><div id="root"></div></body></html>',
      {
        url: 'https://example.com/wp-admin/admin.php',
        pretendToBeVisual: true,
      },
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
    Object.keys(testRequire.cache).forEach((file) => {
      if (
        /[\\/]react-dom[\\/]/.test(file) ||
        /[\\/]common[\\/]datepicker[\\/]/.test(file) ||
        /[\\/]newsletters[\\/]send[\\/]date-text/.test(file)
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
        return { MailPoet: { wpWeekStartsOn: 1 } };
      }
      if (request === '@wordpress/i18n') {
        return {
          __: (text: string) => `X:${text}`,
          _x: (text: string) => `X:${text}`,
        };
      }
      return originalLoad.apply(this, [request, parent, isMain]);
    };
    ({ createElement } = testRequire('react') as typeof import('react'));
    ({ createRoot } = testRequire(
      'react-dom/client',
    ) as typeof import('react-dom/client'));
    ({ act } = testRequire('react') as { act: typeof act });
    ({ enUS } = testRequire('date-fns/locale/en-US') as { enUS: EnUS });
    ({ Datepicker } = testRequire(
      './assets/js/src/common/datepicker/datepicker.tsx',
    ) as { Datepicker: typeof Datepicker });
    loadDateText = () =>
      testRequire('./assets/js/src/newsletters/send/date-text.tsx');
    restoreLoader = () => {
      moduleWithLoader[moduleLoadProperty] = originalLoad;
    };
  });

  after(() => {
    restoreLoader();
    dom.window.close();
    delete (global as { document?: unknown }).document;
    delete (global as { window?: unknown }).window;
    delete (global as { Element?: unknown }).Element;
    delete (global as { Node?: unknown }).Node;
    delete (global as { HTMLElement?: unknown }).HTMLElement;
  });

  it('uses the translated locale when no locale is passed', () => {
    root = createRoot(document.getElementById('root'));
    act(() =>
      root.render(
        createElement(Datepicker, {
          selected: new Date('2026-10-24T00:00:00'),
          onChange: () => undefined,
          dateFormat: 'yyyy-MM-dd',
        }),
      ),
    );
    const input = document.querySelector('input');
    act(() => {
      input.focus();
      input.click();
    });
    const firstDayName = document.querySelector('.react-datepicker__day-name');
    act(() => root.unmount());
    expect(firstDayName.textContent).to.contain('X:Mon');
  });

  it('does not change the shared English locale', () => {
    loadDateText();
    expect(enUS.options.weekStartsOn).to.equal(0);
    expect(enUS.localize.month(0)).to.equal('January');
  });
});
