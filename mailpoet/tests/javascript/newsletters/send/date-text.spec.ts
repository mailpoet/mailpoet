import NodeModule from 'module';

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type ChangeEvent = { target: { name?: string; value?: string } };
type DateTextInstance = { onChange: (value: Date | null) => void };
type DateTextModule = {
  DateText: new (props: Record<string, unknown>) => DateTextInstance;
};

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as {
    createRequire: (path: string) => {
      (path: string): unknown;
      resolve: (path: string) => string;
      cache: Record<string, unknown>;
    };
  }
).createRequire(`${process.cwd()}/package.json`);
const dateTextPath = './assets/js/src/newsletters/send/date-text.tsx';

describe('Schedule DateText', () => {
  let dateTextModule: DateTextModule;
  let calls: ChangeEvent[];
  let dateText: DateTextInstance;

  before(() => {
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
    // Load a fresh copy bound to this spec's `mailpoet` stub, and drop it
    // afterwards so other specs do not get it.
    const resolvedPath = testRequire.resolve(dateTextPath);
    delete testRequire.cache[resolvedPath];
    try {
      dateTextModule = testRequire(dateTextPath) as DateTextModule;
    } finally {
      moduleWithLoader[moduleLoadProperty] = originalLoad;
      delete testRequire.cache[resolvedPath];
    }
  });

  beforeEach(() => {
    calls = [];
    dateText = new dateTextModule.DateText({
      name: 'date',
      storageFormat: 'Y-m-d',
      onChange: (event: ChangeEvent) => calls.push(event),
    });
  });

  it('reports a picked day in the storage format', () => {
    dateText.onChange(new Date(2026, 9, 25));
    expect(calls).to.have.length(1);
    expect(calls[0].target.name).to.equal('date');
    expect(calls[0].target.value).to.equal('2026-10-25');
  });

  it('ignores a cleared date', () => {
    dateText.onChange(null);
    expect(calls).to.have.length(0);
  });
});
