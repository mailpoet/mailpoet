import moment, { Moment } from 'moment';
import { MailPoetDate } from '../../../assets/js/src/date';

describe('MailPoetDate', () => {
  describe('isInFuture', () => {
    let now: Moment | undefined;
    beforeEach(() => {
      now = moment(Date.now());
    });

    it('Should work correctly for present', () => {
      expect(MailPoetDate.isInFuture(now.toISOString(), now)).to.equal(false);
    });

    it('Should work correctly for future dates', () => {
      const tomorrow = now.clone().add(1, 'days').toISOString();

      expect(
        MailPoetDate.isInFuture(
          now.clone().add(1, 'seconds').toISOString(),
          now,
        ),
        '1 second in future',
      ).to.equal(true);
      expect(MailPoetDate.isInFuture(tomorrow, now.valueOf())).to.equal(true);
    });

    it('Should work correctly for past dates ', () => {
      const yesterday = now.clone().add(-1, 'days');

      expect(
        MailPoetDate.isInFuture(
          now.clone().add(-1, 'seconds').toISOString(),
          now,
        ),
        '1 second in the past',
      ).to.equal(false);
      expect(
        MailPoetDate.isInFuture(yesterday.toISOString(), now),
        'yesterday',
      ).to.equal(false);
    });
  });

  describe('site time', () => {
    const globalWithWindow = global as unknown as { window?: unknown };
    let originalWindow: unknown;

    // todayOffset is the offset WordPress reports for the day the page loads.
    const setSite = (timezone: string, todayOffset: string) => {
      globalWithWindow.window = {
        mailpoet_date_timezone: timezone,
        mailpoet_date_offset: todayOffset,
      };
    };

    beforeEach(() => {
      originalWindow = globalWithWindow.window;
    });

    afterEach(() => {
      globalWithWindow.window = originalWindow;
    });

    const conversions: [string, string, string, string, string][] = [
      // [case, time zone, today's offset, site time, UTC]
      [
        'a November date picked in October',
        'America/New_York',
        '-4',
        '2026-11-10 00:00:00',
        '2026-11-10 05:00:00',
      ],
      [
        'a spring date picked in winter',
        'America/New_York',
        '-5',
        '2027-03-20 08:00:00',
        '2027-03-20 12:00:00',
      ],
      [
        'a date with the same offset as today',
        'America/New_York',
        '-5',
        '2026-12-01 08:00:00',
        '2026-12-01 13:00:00',
      ],
      [
        'the last hour before the clocks go back',
        'America/New_York',
        '-4',
        '2026-11-01 00:30:00',
        '2026-11-01 04:30:00',
      ],
      [
        'the first hour after the clocks go back',
        'America/New_York',
        '-4',
        '2026-11-01 03:00:00',
        '2026-11-01 08:00:00',
      ],
      [
        'a winter date picked in summer, east of UTC',
        'Europe/Prague',
        '2',
        '2026-12-20 09:00:00',
        '2026-12-20 08:00:00',
      ],
      [
        'a summer date picked in winter, east of UTC',
        'Europe/Prague',
        '1',
        '2027-04-05 08:00:00',
        '2027-04-05 06:00:00',
      ],
      [
        'a southern winter date picked in the southern summer',
        'Australia/Sydney',
        '11',
        '2027-06-01 08:00:00',
        '2027-05-31 22:00:00',
      ],
      [
        'a half-hour zone without clock changes',
        'Asia/Kolkata',
        '5.5',
        '2026-11-10 00:00:00',
        '2026-11-09 18:30:00',
      ],
    ];

    conversions.forEach(([name, timezone, todayOffset, siteTime, utc]) => {
      it(`converts ${name} to UTC`, () => {
        setSite(timezone, todayOffset);
        expect(MailPoetDate.toGmtDatetimeString(siteTime)).to.equal(utc);
      });

      it(`shows ${name} in site time`, () => {
        setSite(timezone, todayOffset);
        expect(MailPoetDate.datetimeString(utc)).to.equal(siteTime);
      });
    });

    it('takes the first of two identical times when the clocks go back', () => {
      setSite('America/New_York', '-5');
      expect(MailPoetDate.toGmtDatetimeString('2026-11-01 01:30:00')).to.equal(
        '2026-11-01 05:30:00',
      );
      setSite('Europe/Prague', '1');
      expect(MailPoetDate.toGmtDatetimeString('2026-10-25 02:30:00')).to.equal(
        '2026-10-25 00:30:00',
      );
    });

    it('moves a time the clocks skip to the hour after', () => {
      setSite('America/New_York', '-4');
      expect(MailPoetDate.toGmtDatetimeString('2027-03-14 02:30:00')).to.equal(
        '2027-03-14 07:30:00',
      );
      setSite('Europe/Prague', '2');
      expect(MailPoetDate.toGmtDatetimeString('2027-03-28 02:30:00')).to.equal(
        '2027-03-28 01:30:00',
      );
    });

    it('uses the offset of the formatted date, not of today', () => {
      setSite('America/New_York', '-5');
      expect(
        MailPoetDate.format('2026-07-10 14:00:00', { format: 'Y-m-d H:i' }),
      ).to.equal('2026-07-10 10:00');
    });

    it('gives the same result when one options object is used twice', () => {
      setSite('America/New_York', '-5');
      const options = { format: 'Y-m-d H:i' };
      expect(MailPoetDate.format('2026-07-10 14:00:00', options)).to.equal(
        '2026-07-10 10:00',
      );
      expect(MailPoetDate.format('2026-07-10 14:00:00', options)).to.equal(
        '2026-07-10 10:00',
      );
    });

    it('keeps the fixed offset on a site without a named time zone', () => {
      setSite('', '-4');
      expect(MailPoetDate.toGmtDatetimeString('2026-11-10 00:00:00')).to.equal(
        '2026-11-10 04:00:00',
      );
      expect(MailPoetDate.datetimeString('2026-11-10 04:00:00')).to.equal(
        '2026-11-10 00:00:00',
      );
    });

    it('keeps the fixed offset for a time zone the browser does not know', () => {
      setSite('Mars/Olympus_Mons', '2');
      expect(MailPoetDate.toGmtDatetimeString('2026-11-10 00:00:00')).to.equal(
        '2026-11-09 22:00:00',
      );
      expect(MailPoetDate.datetimeString('2026-11-09 22:00:00')).to.equal(
        '2026-11-10 00:00:00',
      );
    });

    it('still reports a missing date as invalid', () => {
      setSite('America/New_York', '-5');
      expect(MailPoetDate.datetimeString(null)).to.equal('Invalid date');
      expect(MailPoetDate.toGmtDatetimeString(null)).to.equal('Invalid date');
    });

    it('lets an explicit offset win over the time zone', () => {
      setSite('America/New_York', '-5');
      expect(
        MailPoetDate.format('2026-07-10 14:00:00', {
          format: 'H:i',
          offset: 0,
        }),
      ).to.equal('14:00');
      expect(
        MailPoetDate.format('2026-07-10 14:00:00', {
          format: 'H:i',
          offset: 2,
        }),
      ).to.equal('16:00');
    });
  });
});
