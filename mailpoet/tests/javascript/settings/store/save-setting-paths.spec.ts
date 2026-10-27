import { expect } from 'chai';
import NodeModule from 'module';

type Step = { type?: string; [key: string]: unknown };
type StepGenerator = Generator<Step, Step, unknown>;
type ActionsModule = {
  saveSettingPaths: (paths: string[][]) => StepGenerator;
};
type SettingsState = {
  data: Record<string, unknown>;
  originalData: Record<string, unknown>;
  save: { inProgress: boolean; error: unknown; hasUnsavedChanges: boolean };
};
type ReducerModule = {
  createReducer: (
    defaultValue: SettingsState,
  ) => (state: SettingsState, action: Step) => SettingsState;
};
type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = ((path: string) => unknown) & {
  resolve: (path: string) => string;
  cache: Record<string, unknown>;
};

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

const actionsPath = testRequire.resolve(
  './assets/js/src/settings/store/actions/settings.ts',
);
const normalizePath = testRequire.resolve(
  './assets/js/src/settings/store/normalize-settings.ts',
);
// Other specs may have loaded these with their own mocks
const clearModuleCache = () => {
  delete testRequire.cache[actionsPath];
  delete testRequire.cache[normalizePath];
};

// The store holds an invalid, unsaved edit on another tab.
const storeSettings = {
  mss_super_sending: { enabled: '1' },
  subscriber_email_notification: { address: 'not-an-email' },
};

let originalModuleLoad: ModuleLoader;
let settingsActions: ActionsModule;
let reducerModule: ReducerModule;

const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === '@wordpress/data') {
      return { select: () => ({ getSettings: () => storeSettings }) };
    }
    if (request === 'common') {
      return { t: (key: string) => key, isTruthy: () => true };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

// Steps a generator to the end, answering CALL_API with the response
const runToEnd = (generator: StepGenerator, response: unknown) => {
  let step = generator.next();
  while (!step.done) {
    step = generator.next(step.value.type === 'CALL_API' ? response : null);
  }
  return step.value;
};

describe('Saving only some settings', () => {
  before(() => {
    clearModuleCache();
    installModuleMocks();
    settingsActions = testRequire(actionsPath) as ActionsModule;
    reducerModule = testRequire(
      './assets/js/src/settings/store/create-reducer.ts',
    ) as ReducerModule;
    restoreModuleMocks();
  });

  after(clearModuleCache);

  describe('saveSettingPaths', () => {
    it('posts only the given settings', () => {
      const generator = settingsActions.saveSettingPaths([
        ['mss_super_sending', 'enabled'],
      ]);
      generator.next(); // SAVE_STARTED

      const apiCall = generator.next().value;
      expect(apiCall.type).to.equal('CALL_API');
      expect(apiCall.data).to.deep.equal({ 'mss_super_sending.enabled': '1' });
    });

    it('copies back the saved settings and the ones the server changed', () => {
      const generator = settingsActions.saveSettingPaths([
        ['mss_super_sending', 'enabled'],
      ]);
      const response = {
        success: true,
        res: {
          data: {
            mss_super_sending: { enabled: '1' },
            sender: { name: '', address: 'me@example.com' },
            reply_to: null,
          },
          meta: {
            // The settings page doesn't use "source", so it isn't copied back
            changed_settings: [
              'mss_super_sending.source',
              'sender.address',
              'reply_to',
            ],
          },
        },
      };

      expect(runToEnd(generator, response)).to.deep.equal({
        type: 'PARTIAL_SAVE_DONE',
        values: [
          { path: ['mss_super_sending', 'enabled'], value: '1' },
          { path: ['sender', 'address'], value: 'me@example.com' },
          { path: ['reply_to'], value: { name: '', address: '' } },
        ],
      });
    });
  });

  describe('reducer', () => {
    const makeState = (
      data: Record<string, unknown>,
      originalData: Record<string, unknown>,
    ): SettingsState => ({
      data,
      originalData,
      save: { inProgress: true, error: null, hasUnsavedChanges: true },
    });
    const reduce = (state: SettingsState, action: Step) =>
      reducerModule.createReducer(state)(state, action);

    it('marks only the saved settings as saved', () => {
      const state = makeState(
        { a: { enabled: '1' }, b: 'edited' },
        { a: { enabled: '' }, b: 'original' },
      );

      const newState = reduce(state, {
        type: 'PARTIAL_SAVE_DONE',
        values: [{ path: ['a', 'enabled'], value: '1' }],
      });

      expect(newState.data).to.deep.equal({ a: { enabled: '1' }, b: 'edited' });
      expect(newState.originalData).to.deep.equal({
        a: { enabled: '1' },
        b: 'original',
      });
      expect(newState.save).to.deep.equal({
        inProgress: false,
        error: null,
        hasUnsavedChanges: true,
      });
    });

    it('has no unsaved changes when the saved settings were the only edit', () => {
      const state = makeState({ a: { enabled: '1' } }, { a: { enabled: '' } });

      const newState = reduce(state, {
        type: 'PARTIAL_SAVE_DONE',
        values: [{ path: ['a', 'enabled'], value: '1' }],
      });

      expect(newState.save.hasUnsavedChanges).to.equal(false);
    });
  });
});
