import { expect } from 'chai';
import NodeModule from 'module';

type Step = { type?: string; [key: string]: unknown };
type StepGenerator = Generator<Step, Step, unknown>;
type ActionsModule = {
  recalculateSubscribersScore: () => StepGenerator;
  saveSettings: () => StepGenerator;
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
let recalculateActions: Pick<ActionsModule, 'recalculateSubscribersScore'>;

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
    if (request === 'mailpoet') {
      return { MailPoet: { Modal: { loading: () => undefined } } };
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
    recalculateActions = testRequire(
      './assets/js/src/settings/store/actions/recalculate-subscribers-score.ts',
    ) as Pick<ActionsModule, 'recalculateSubscribersScore'>;
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

  describe('saveSettings', () => {
    it('treats the settings as saved', () => {
      const response = { success: true, res: { data: {}, meta: {} } };

      expect(runToEnd(settingsActions.saveSettings(), response)).to.deep.equal({
        type: 'FULL_SAVE_DONE',
      });
    });
  });

  describe('recalculateSubscribersScore', () => {
    it('leaves the unsaved changes as they are', () => {
      expect(
        runToEnd(recalculateActions.recalculateSubscribersScore(), {
          success: true,
        }),
      ).to.equal(null);
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

    it('takes the value of a setting the server changed', () => {
      const state = makeState(
        { mta: { method: 'MailPoet' }, sender: { address: 'wordpress@x.com' } },
        { mta: { method: 'SMTP' }, sender: { address: 'wordpress@x.com' } },
      );

      // Activating MSS replaced the saved sender on the server
      const newState = reduce(state, {
        type: 'PARTIAL_SAVE_DONE',
        values: [
          { path: ['mta', 'method'], value: 'MailPoet' },
          { path: ['sender', 'address'], value: 'me@x.com' },
        ],
      });

      expect(newState.data.sender).to.deep.equal({ address: 'me@x.com' });
      expect(newState.save.hasUnsavedChanges).to.equal(false);
    });

    it('switches the warning off on SAVE_DONE', () => {
      // The key check ends with SAVE_DONE and reloads the page right after
      const state = makeState({ a: 'edited' }, { a: '' });

      const newState = reduce(state, { type: 'SAVE_DONE' });

      expect(newState.save.hasUnsavedChanges).to.equal(false);
    });

    it('keeps unsaved edits unsaved after SAVE_DONE', () => {
      const state = makeState({ a: 'edited', b: '' }, { a: '', b: '' });

      let newState = reduce(state, { type: 'SAVE_DONE' });
      newState = reduce(newState, {
        type: 'SET_SETTING',
        path: ['b'],
        value: 'x',
      });
      newState = reduce(newState, {
        type: 'SET_SETTING',
        path: ['b'],
        value: '',
      });

      expect(newState.save.hasUnsavedChanges).to.equal(true);
    });

    it('marks settings saved elsewhere as saved', () => {
      const state = makeState({ a: 'saved', b: 'edited' }, { a: '', b: '' });

      const newState = reduce(state, {
        type: 'MARK_SETTINGS_SAVED',
        paths: [['a']],
      });

      expect(newState.originalData).to.deep.equal({ a: 'saved', b: '' });
      expect(newState.save.hasUnsavedChanges).to.equal(true);
    });

    it('treats the settings as saved after a full save', () => {
      const state = makeState({ a: 'saved' }, { a: '' });

      const newState = reduce(state, { type: 'FULL_SAVE_DONE' });

      expect(newState.originalData).to.deep.equal({ a: 'saved' });
      expect(newState.save.hasUnsavedChanges).to.equal(false);
    });
  });
});
