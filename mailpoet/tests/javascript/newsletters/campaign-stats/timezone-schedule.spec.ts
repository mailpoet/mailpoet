import NodeModule from 'module';
import type { Field } from '@wordpress/dataviews';

type ScheduleModule = {
  timezoneScheduleFields: Field<unknown>[];
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

const schedulePath =
  './assets/js/src/newsletters/campaign-stats/timezone-schedule.tsx';
const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let scheduleModule: ScheduleModule;
let originalModuleLoad: ModuleLoader;

// The cell renderers read the MailPoet global; the field config does not.
const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === 'mailpoet') {
      return { MailPoet: {} };
    }
    return originalModuleLoad.call(this, request, parent, isMain);
  };
};

const getField = (id: string) =>
  scheduleModule.timezoneScheduleFields.find((field) => field.id === id);

describe('timezone schedule fields', () => {
  before(() => {
    installModuleMocks();
    scheduleModule = testRequire(schedulePath) as ScheduleModule;
  });

  after(() => {
    // Don't leave the copy built with mocked imports for later specs.
    delete testRequire.cache[testRequire.resolve(schedulePath)];
    (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
      originalModuleLoad;
  });

  it('does not offer hiding or filtering on the time zone title column', () => {
    const timezone = getField('timezone');

    expect(timezone.enableHiding).to.equal(false);
    expect(timezone.filterBy).to.equal(false);
  });

  it('keeps sorting on the time zone title column', () => {
    expect(getField('timezone').enableSorting).to.equal(true);
  });
});
