import NodeModule from 'module';
import { isTruthy } from 'common/utils';

type NormalizeSettingsModule = {
  normalizeSettings: (data: Record<string, unknown>) => {
    subscribe: Record<'on_comment' | 'on_register', { label: string }>;
    allow_sending_in_non_production: string;
  };
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

const normalizeSettingsPath =
  './assets/js/src/settings/store/normalize-settings.ts';
const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let normalizeSettingsModule: NormalizeSettingsModule;

const loadWithMockedCommon = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  const originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === 'common') {
      return {
        t: (key: string) => (key === 'yesAddMe' ? 'Default label' : key),
        isTruthy,
      };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
  normalizeSettingsModule = testRequire(
    normalizeSettingsPath,
  ) as NormalizeSettingsModule;
  moduleWithLoader[moduleLoadProperty] = originalModuleLoad;
};

const normalizeSubscribeLabels = (label: unknown) =>
  normalizeSettingsModule.normalizeSettings({
    subscribe: { on_comment: { label }, on_register: { label } },
  }).subscribe;

describe('normalizeSettings subscribe labels', () => {
  before(loadWithMockedCommon);

  after(() => {
    delete testRequire.cache[testRequire.resolve(normalizeSettingsPath)];
  });

  it('keeps a saved label', () => {
    const subscribe = normalizeSubscribeLabels('Join our list');
    expect(subscribe.on_comment.label).to.equal('Join our list');
    expect(subscribe.on_register.label).to.equal('Join our list');
  });

  it('uses the default label when no label is saved', () => {
    const subscribe = normalizeSubscribeLabels(undefined);
    expect(subscribe.on_comment.label).to.equal('Default label');
    expect(subscribe.on_register.label).to.equal('Default label');
  });

  it('uses the default label when the saved label is empty', () => {
    const subscribe = normalizeSubscribeLabels('');
    expect(subscribe.on_comment.label).to.equal('Default label');
    expect(subscribe.on_register.label).to.equal('Default label');
  });

  it('uses the default label when the saved label is whitespace', () => {
    const subscribe = normalizeSubscribeLabels('   ');
    expect(subscribe.on_comment.label).to.equal('Default label');
    expect(subscribe.on_register.label).to.equal('Default label');
  });
});

describe('normalizeSettings non-production sending', () => {
  before(loadWithMockedCommon);

  after(() => {
    delete testRequire.cache[testRequire.resolve(normalizeSettingsPath)];
  });

  const normalize = (data: Record<string, unknown>) =>
    normalizeSettingsModule.normalizeSettings(data)
      .allow_sending_in_non_production;

  it('turns sending off when the setting is missing', () => {
    expect(normalize({})).to.equal('');
  });

  it('keeps sending on when the setting is 1', () => {
    expect(normalize({ allow_sending_in_non_production: '1' })).to.equal('1');
  });

  it('keeps sending off when the setting is empty', () => {
    expect(normalize({ allow_sending_in_non_production: '' })).to.equal('');
  });
});
