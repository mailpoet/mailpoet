import NodeModule from 'module';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

type NoticeModule = { EmailVolumeLimitNotice: React.ComponentType };
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

const defaultTranslation =
  'To continue please [link]upgrade your plan[/link], or wait until <b>[date]</b>. Thank you.';
let translation = defaultTranslation;

let noticeModule: NoticeModule;
let originalModuleLoad: ModuleLoader;

const installModuleMocks = () => {
  const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
  originalModuleLoad = moduleWithLoader[moduleLoadProperty];
  moduleWithLoader[moduleLoadProperty] = function loadModule(
    request: string,
    parent: unknown,
    isMain: boolean,
  ) {
    if (request === 'mailpoet') {
      return {
        MailPoet: {
          emailVolumeLimitReached: true,
          emailVolumeLimit: 0,
          pluginPartialKey: 'key',
          I18n: {
            t: (key: string) =>
              key === 'toContinueUpgradeYourPlanOrWaitUntil'
                ? translation
                : key,
          },
          Date: { short: () => '1 Nov 2026' },
          MailPoetComUrlFactory: {
            getUpgradeUrl: () => 'https://example.com/upgrade',
          },
        },
      };
    }
    if (request === 'notices/notice') {
      return {
        Notice: ({ children }: { children: React.ReactNode }) =>
          React.createElement('div', null, children),
      };
    }
    if (request === 'common') {
      return { withBoundary: (component: unknown) => component };
    }
    return originalModuleLoad.apply(this, [request, parent, isMain]);
  };
};

const restoreModuleMocks = () => {
  (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
    originalModuleLoad;
};

describe('email volume limit notice', () => {
  before(() => {
    installModuleMocks();
    try {
      noticeModule = testRequire(
        './assets/js/src/notices/email-volume-limit-notice.tsx',
      ) as NoticeModule;
    } finally {
      restoreModuleMocks();
    }
  });

  const render = () =>
    renderToStaticMarkup(
      React.createElement(noticeModule.EmailVolumeLimitNotice),
    );

  afterEach(() => {
    translation = defaultTranslation;
  });

  it('keeps the text after the date and the upgrade link', () => {
    const markup = render();
    expect(markup).to.include('<b>1 Nov 2026</b>. Thank you.');
    expect(markup).to.include(
      '<a target="_blank" rel="noreferrer" href="https://example.com/upgrade">upgrade your plan</a>',
    );
  });

  it('replaces the date when other text follows it', () => {
    translation =
      'Bitte [link]upgraden[/link], oder wait am <b>[date]</b> automatisch fortgesetzt.';
    const markup = render();
    expect(markup).to.include('<b>1 Nov 2026</b> automatisch fortgesetzt.');
    expect(markup).not.to.include('[date]');
  });

  it('replaces every date token', () => {
    translation = '<b>[date]</b> and <b>[date]</b>';
    expect(render()).to.include('<b>1 Nov 2026</b> and <b>1 Nov 2026</b>');
  });
});
