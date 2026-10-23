import NodeModule from 'module';
import { MailPoetDate } from '../../../../../assets/js/src/date';

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type BuildShortcode =
  typeof import('../../../../../assets/js/src/settings/pages/basics/build-shortcode').buildShortcode;

type TestRequire = (path: string) => unknown;

const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);
const moduleLoadProperty = '_load';
const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;

const base = {
  name: 'mailpoet_archive',
  segments: [] as unknown[],
  startDate: null,
  endDate: null,
  lastNDays: '',
  subjectContains: '',
  limit: '',
};

describe('buildShortcode', () => {
  const originalTz = process.env.TZ;
  const originalLoad = moduleWithLoader[moduleLoadProperty];
  const originalWindow = global.window;
  let buildShortcode: BuildShortcode;

  before(() => {
    // Browser ahead of the site (UTC+2 vs UTC+0): a picked local midnight
    // is the previous day in the site zone.
    process.env.TZ = 'Europe/Prague';
    global.window = {
      mailpoet_date_offset: '0',
    } as unknown as Window & typeof globalThis;
    moduleWithLoader[moduleLoadProperty] = (request, parent, isMain) =>
      request === 'mailpoet'
        ? { MailPoet: { Date: MailPoetDate } }
        : originalLoad(request, parent, isMain);
    ({ buildShortcode } = testRequire(
      `${process.cwd()}/assets/js/src/settings/pages/basics/build-shortcode`,
    ) as { buildShortcode: BuildShortcode });
  });

  after(() => {
    moduleWithLoader[moduleLoadProperty] = originalLoad;
    global.window = originalWindow;
    if (originalTz === undefined) {
      delete process.env.TZ;
    } else {
      process.env.TZ = originalTz;
    }
  });

  it('writes the picked start date as the same calendar day', () => {
    expect(
      buildShortcode({ ...base, startDate: new Date(2026, 9, 15) } as never),
    ).to.equal('[mailpoet_archive start_date="2026-10-15"]');
  });

  it('writes the picked end date as the same calendar day', () => {
    expect(
      buildShortcode({ ...base, endDate: new Date(2026, 9, 15) } as never),
    ).to.equal('[mailpoet_archive end_date="2026-10-15"]');
  });

  it('keeps the other attributes unchanged', () => {
    expect(
      buildShortcode({
        ...base,
        segments: [1, 2],
        lastNDays: '7',
        subjectContains: 'News',
        limit: '5',
      } as never),
    ).to.equal(
      '[mailpoet_archive segments="1,2" in_the_last_days="7" subject_contains="News" limit="5"]',
    );
  });
});
