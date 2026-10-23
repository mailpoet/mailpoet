import NodeModule from 'module';
import { MailPoetDate } from '../../../../../../assets/js/src/date';

type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = (path: string) => unknown;
type DateHelpers =
  typeof import('../../../../../../assets/js/src/segments/dynamic/dynamic-segments-filters/fields/date-helpers');

const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);
const moduleLoadProperty = '_load';
const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;

describe('date helpers', () => {
  const originalTz = process.env.TZ;
  const originalLoad = moduleWithLoader[moduleLoadProperty];
  const originalWindow = global.window;
  let helpers: DateHelpers;

  before(() => {
    global.window = {
      mailpoet_date_offset: '0',
    } as unknown as Window & typeof globalThis;
    moduleWithLoader[moduleLoadProperty] = (request, parent, isMain) =>
      request === 'mailpoet'
        ? { MailPoet: { Date: MailPoetDate } }
        : originalLoad(request, parent, isMain);
    helpers = testRequire(
      `${process.cwd()}/assets/js/src/segments/dynamic/dynamic-segments-filters/fields/date-helpers`,
    ) as DateHelpers;
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

  ['Europe/Prague', 'Asia/Tokyo', 'America/New_York'].forEach((zone) => {
    describe(`browser in ${zone}, site at UTC+0`, () => {
      before(() => {
        process.env.TZ = zone;
      });

      it('keeps the picked calendar day', () => {
        expect(helpers.convertDateToString(new Date(2026, 9, 15))).to.equal(
          '2026-10-15',
        );
        expect(helpers.convertDateToString(new Date(2026, 0, 1))).to.equal(
          '2026-01-01',
        );
      });

      it('round-trips through parseDate', () => {
        const parsed = helpers.parseDate('2026-10-15');
        expect(parsed).to.not.equal(undefined);
        expect(helpers.convertDateToString(parsed)).to.equal('2026-10-15');
      });
    });
  });

  it('returns undefined for null', () => {
    expect(helpers.convertDateToString(null)).to.equal(undefined);
  });

  it('throws for an array', () => {
    expect(() =>
      helpers.convertDateToString([new Date(), new Date()]),
    ).to.throw('single date');
  });
});
