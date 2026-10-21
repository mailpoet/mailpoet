import NodeModule from 'module';
import type { Field } from '@wordpress/dataviews';

type FieldsModule = {
  automationFields: Field<unknown>[];
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

const fieldsPath = './assets/js/src/automation/listing/fields.tsx';
const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let fieldsModule: FieldsModule;
let originalModuleLoad: ModuleLoader;

// The cell renderers read window globals on import; the field config does not.
const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === '../components/status') {
      return { AutomationStatus: () => null };
    }
    if (request === '../components/statistics') {
      return { Statistics: () => null };
    }
    if (request === './urls') {
      return { getAutomationEditorUrl: () => '' };
    }
    return originalModuleLoad.call(this, request, parent, isMain);
  };
};

const getField = (id: string) =>
  fieldsModule.automationFields.find((field) => field.id === id);

describe('automation listing fields', () => {
  before(() => {
    installModuleMocks();
    fieldsModule = testRequire(fieldsPath) as FieldsModule;
  });

  after(() => {
    // Don't leave the copy built with mocked imports for later specs.
    delete testRequire.cache[testRequire.resolve(fieldsPath)];
    (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
      originalModuleLoad;
  });

  it('does not offer hiding or filtering on the name title column', () => {
    const name = getField('name');

    expect(name.enableHiding).to.equal(false);
    expect(name.filterBy).to.equal(false);
  });

  it('keeps sorting and search on the name title column', () => {
    const name = getField('name');

    expect(name.enableSorting).to.equal(true);
    expect(name.enableGlobalSearch).to.equal(true);
  });

  it('keeps the subscribers and status columns hideable', () => {
    expect(getField('subscribers').enableHiding).not.to.equal(false);
    expect(getField('status').enableHiding).not.to.equal(false);
  });
});
