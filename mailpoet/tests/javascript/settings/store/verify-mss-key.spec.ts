import { expect } from 'chai';
import NodeModule from 'module';

type Step = { type?: string; [key: string]: unknown };
type StepGenerator = Generator<Step, unknown, unknown>;
type ActionsModule = {
  verifyMssKey: (
    key: string,
    keepSendingMethodForVerifiedKey?: boolean,
  ) => StepGenerator;
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
  './assets/js/src/settings/store/actions/mss-and-premium.ts',
);

let settings: Record<string, unknown>;
let mssActions: ActionsModule;
let MssStatus: Record<string, number>;

const installModuleMocks = (): ModuleLoader => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  const originalModuleLoad = moduleWithLoader[moduleLoadProperty];
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
  return originalModuleLoad;
};

// Steps a generator, answering each yielded CALL_API from the given results.
const run = (generator: StepGenerator, apiResults: unknown[]) => {
  const steps: Step[] = [];
  const results = [...apiResults];
  let step = generator.next();
  while (!step.done) {
    const value = step.value as Step;
    steps.push(value);
    step = generator.next(value.type === 'CALL_API' ? results.shift() : null);
  }
  return { steps, returned: step.value as Step };
};

const ok = (data: unknown = {}) => ({
  success: true,
  error: [],
  res: { data, meta: {} },
});
const keyState = { state: 'valid', code: 200, data: { is_approved: true } };
const validKey = ok({ state: 'valid', result: keyState });
// Answers a settings save, so a run that saves fails on the assertions.
const savedSettings = ok({
  mta: { method: 'MailPoet', mailpoet_api_key_state: keyState },
});
const isSettingsSave = (step: Step) =>
  step.type === 'CALL_API' && step.endpoint === 'settings';

describe('verifyMssKey', () => {
  before(() => {
    // Other specs may have loaded the actions with their own store mock
    delete testRequire.cache[actionsPath];
    const originalModuleLoad = installModuleMocks();
    mssActions = testRequire(actionsPath) as ActionsModule;
    ({ MssStatus } = testRequire('./assets/js/src/settings/store/types.ts') as {
      MssStatus: Record<string, number>;
    });
    (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
      originalModuleLoad;
  });

  after(() => {
    delete testRequire.cache[actionsPath];
  });

  beforeEach(() => {
    settings = {
      mta_group: 'website',
      mta: {
        method: 'PHPMail',
        mailpoet_api_key: 'key',
        mailpoet_api_key_state: { state: 'valid', code: 200 },
      },
      signup_confirmation: { enabled: '0' },
    };
  });

  const expectSwitchToMailPoet = (steps: Step[]) => {
    const save = steps.find(isSettingsSave);
    expect(save).to.not.equal(undefined);
    const data = save.data as {
      mta_group: string;
      mta: { method: string };
    };
    expect(data.mta_group).to.equal('mailpoet');
    expect(data.mta.method).to.equal('MailPoet');
    expect(steps.map((step) => step.type)).to.include('FULL_SAVE_DONE');
  };

  it('keeps the sending method when re-verifying the stored valid key', () => {
    const { steps, returned } = run(mssActions.verifyMssKey('key', true), [
      validKey,
      savedSettings,
    ]);

    expect(steps.some(isSettingsSave)).to.equal(false);
    expect(steps).to.deep.include({
      type: 'MARK_SETTINGS_SAVED',
      paths: [['mta', 'mailpoet_api_key_state']],
    });
    const update = steps.find((step) => step.type === 'SET_SETTINGS');
    expect(update.value).to.deep.include({
      mta_group: 'website',
      mta: {
        method: 'PHPMail',
        mailpoet_api_key: 'key',
        mailpoet_api_key_state: keyState,
      },
    });
    expect(steps.map((step) => step.type)).to.include('REFRESH_HELD_CAMPAIGNS');
    expect(returned.fields).to.include({
      mssStatus: MssStatus.VALID_MSS_NOT_ACTIVE,
    });
  });

  it('switches the sending method for a new key', () => {
    const { steps } = run(mssActions.verifyMssKey('new-key', true), [
      validKey,
      savedSettings,
    ]);

    expectSwitchToMailPoet(steps);
  });

  it('switches the sending method when the stored key was not valid', () => {
    (settings.mta as Record<string, unknown>).mailpoet_api_key_state = {
      state: 'invalid',
      code: 401,
    };
    const { steps } = run(mssActions.verifyMssKey('key', true), [
      validKey,
      savedSettings,
    ]);

    expectSwitchToMailPoet(steps);
  });

  it('saves the settings when it already sends with MailPoet', () => {
    (settings.mta as Record<string, unknown>).method = 'MailPoet';
    const { steps, returned } = run(mssActions.verifyMssKey('key', true), [
      validKey,
      savedSettings,
    ]);

    expect(steps.some(isSettingsSave)).to.equal(true);
    expect(returned.fields).to.include({
      mssStatus: MssStatus.VALID_MSS_ACTIVE,
    });
  });

  it('switches the sending method for the stored key by default', () => {
    const { steps } = run(mssActions.verifyMssKey('key'), [
      validKey,
      savedSettings,
    ]);

    expectSwitchToMailPoet(steps);
  });
});
