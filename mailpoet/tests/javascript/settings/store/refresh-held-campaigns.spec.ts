import { expect } from 'chai';
import NodeModule from 'module';

type Step = { type?: string; [key: string]: unknown };
type StepGenerator = Generator<Step, unknown, unknown>;
type ActionsModule = {
  saveSettings: () => StepGenerator;
  verifyMssKey: (key: string) => StepGenerator;
  verifyPremiumKey: (key: string) => StepGenerator;
};
type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = (path: string) => unknown;

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

const settings = {
  mta: {},
  signup_confirmation: { enabled: '0' },
};

let originalModuleLoad: ModuleLoader;
let settingsActions: ActionsModule;
let mssActions: ActionsModule;

const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === '@wordpress/data') {
      return { select: () => ({ getSettings: () => settings }) };
    }
    if (request === 'mailpoet') {
      return { MailPoet: { trackEvent: () => undefined } };
    }
    if (request === 'common') {
      return { t: () => undefined, isTruthy: () => true };
    }
    if (request === 'wp-js-hooks') {
      return { Hooks: { doAction: () => undefined } };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

// Steps a generator, answering each yielded CALL_API from the given results.
const run = (generator: StepGenerator, apiResults: unknown[]): string[] => {
  const types: string[] = [];
  const results = [...apiResults];
  let step = generator.next();
  while (!step.done) {
    const value = step.value as Step;
    types.push(value.type);
    step = generator.next(value.type === 'CALL_API' ? results.shift() : null);
  }
  return types;
};

const ok = (data: unknown = {}, meta: unknown = {}) => ({
  success: true,
  error: [],
  res: { data, meta },
});
const failed = { success: false, error: ['error'], res: undefined };
const settingsData = {
  mta: { mailpoet_api_key_state: { code: 200 } },
};
const refresh = 'REFRESH_HELD_CAMPAIGNS';

describe('Refreshing held campaigns after a sending settings change', () => {
  before(() => {
    installModuleMocks();
    settingsActions = testRequire(
      './assets/js/src/settings/store/actions/settings.ts',
    ) as ActionsModule;
    mssActions = testRequire(
      './assets/js/src/settings/store/actions/mss-and-premium.ts',
    ) as ActionsModule;
    restoreModuleMocks();
  });

  describe('saveSettings', () => {
    it('refreshes after a successful save', () => {
      expect(run(settingsActions.saveSettings(), [ok()])).to.include(refresh);
    });

    it('does not refresh after a failed save', () => {
      expect(run(settingsActions.saveSettings(), [failed])).to.not.include(
        refresh,
      );
    });
  });

  describe('verifyPremiumKey', () => {
    it('refreshes when the key is valid', () => {
      const types = run(mssActions.verifyPremiumKey('key'), [
        ok({}, { premium_plugin_active: true }),
      ]);
      expect(types).to.include(refresh);
    });

    it('marks the stored key as saved', () => {
      const types = run(mssActions.verifyPremiumKey('key'), [
        ok({}, { premium_plugin_active: true }),
      ]);
      expect(types).to.include('MARK_SETTINGS_SAVED');
    });

    it('does not refresh when the key is invalid', () => {
      const types = run(mssActions.verifyPremiumKey('key'), [failed]);
      expect(types).to.not.include(refresh);
    });
  });

  describe('verifyMssKey', () => {
    const validKey = ok({ state: 'valid', result: { data: {} } });

    it('refreshes when the settings save succeeds', () => {
      const types = run(mssActions.verifyMssKey('key'), [
        validKey,
        ok(settingsData),
      ]);
      expect(types).to.include(refresh);
    });

    it('does not refresh when the settings save fails', () => {
      const types = run(mssActions.verifyMssKey('key'), [validKey, failed]);
      expect(types).to.not.include(refresh);
    });

    it('does not refresh when the key is underprivileged', () => {
      const underprivileged = ok({ state: 'valid_underprivileged' });
      const types = run(mssActions.verifyMssKey('key'), [underprivileged]);
      expect(types).to.not.include(refresh);
    });

    it('does not refresh when the key is invalid', () => {
      const types = run(mssActions.verifyMssKey('key'), [failed]);
      expect(types).to.not.include(refresh);
    });
  });
});
