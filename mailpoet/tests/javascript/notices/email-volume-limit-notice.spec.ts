import NodeModule from 'module';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import sinon from 'sinon';
import { MailPoetDate } from '../../../assets/js/src/date';

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
const fixedDate = (): string => '1 Nov 2026';
let shortDate: (date: unknown) => string = fixedDate;

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
          Date: { short: (date: unknown) => shortDate(date) },
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

  const globalWithWindow = global as unknown as { window?: unknown };
  let originalWindow: unknown;

  beforeEach(() => {
    originalWindow = globalWithWindow.window;
    globalWithWindow.window = {};
  });

  afterEach(() => {
    translation = defaultTranslation;
    shortDate = fixedDate;
    globalWithWindow.window = originalWindow;
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

  describe('with the real date formatting', () => {
    let clock: sinon.SinonFakeTimers;
    beforeEach(() => {
      globalWithWindow.window = {
        mailpoet_date_offset: '-5',
        mailpoet_date_format: 'F j, Y',
      };
      clock = sinon.useFakeTimers(new Date('2026-10-22T12:00:00Z'));
      shortDate = (date) => MailPoetDate.short(date as Date);
    });

    afterEach(() => {
      clock.restore();
    });

    it('shows the first day of the next month in the site time zone', () => {
      expect(render()).to.include('<b>November 1, 2026</b>');
    });
  });
});
