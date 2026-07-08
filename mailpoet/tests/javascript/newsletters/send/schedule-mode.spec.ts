import {
  DEFAULT_SCHEDULED_LOCAL_TIME,
  SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
  SCHEDULE_MODE_WEBSITE_TIME,
  getLocalTimeOfDayItems,
  getScheduleMode,
  getScheduleModeOptionChanges,
  getTomorrowLocalDate,
  isLocalDateTimeInFuture,
  normalizeLocalTime,
} from '../../../../assets/js/src/common/newsletter-schedule-mode';

describe('newsletter schedule mode helpers', () => {
  describe('getScheduleMode', () => {
    it('defaults missing and unknown values to website time', () => {
      expect(getScheduleMode(undefined)).to.equal(SCHEDULE_MODE_WEBSITE_TIME);
      expect(getScheduleMode(null)).to.equal(SCHEDULE_MODE_WEBSITE_TIME);
      expect(getScheduleMode('')).to.equal(SCHEDULE_MODE_WEBSITE_TIME);
      expect(getScheduleMode('something_else')).to.equal(
        SCHEDULE_MODE_WEBSITE_TIME,
      );
    });

    it('recognizes the subscriber timezone mode', () => {
      expect(getScheduleMode(SCHEDULE_MODE_SUBSCRIBER_TIMEZONE)).to.equal(
        SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
      );
    });
  });

  describe('getScheduleModeOptionChanges', () => {
    it('clears the local date and time when switching to website time', () => {
      expect(
        getScheduleModeOptionChanges(
          SCHEDULE_MODE_WEBSITE_TIME,
          { scheduledLocalDate: '2026-07-10', scheduledLocalTime: '09:15:00' },
          '2026-07-09',
        ),
      ).to.deep.equal({
        scheduleMode: SCHEDULE_MODE_WEBSITE_TIME,
        scheduledLocalDate: '',
        scheduledLocalTime: '',
      });
    });

    it('seeds defaults when switching to subscriber timezone without values', () => {
      expect(
        getScheduleModeOptionChanges(
          SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
          {},
          '2026-07-09',
        ),
      ).to.deep.equal({
        scheduleMode: SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
        scheduledLocalDate: '2026-07-09',
        scheduledLocalTime: DEFAULT_SCHEDULED_LOCAL_TIME,
      });
    });

    it('keeps existing local date and time when switching to subscriber timezone', () => {
      expect(
        getScheduleModeOptionChanges(
          SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
          { scheduledLocalDate: '2026-08-01', scheduledLocalTime: '21:45:00' },
          '2026-07-09',
        ),
      ).to.deep.equal({
        scheduleMode: SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
        scheduledLocalDate: '2026-08-01',
        scheduledLocalTime: '21:45:00',
      });
    });
  });

  describe('normalizeLocalTime', () => {
    it('pads HH:MM values with seconds', () => {
      expect(normalizeLocalTime('08:15')).to.equal('08:15:00');
    });

    it('keeps HH:MM:SS values unchanged', () => {
      expect(normalizeLocalTime('08:15:00')).to.equal('08:15:00');
    });
  });

  describe('isLocalDateTimeInFuture', () => {
    const now = new Date('2026-07-08T12:00:00');

    it('returns false for missing values', () => {
      expect(isLocalDateTimeInFuture(undefined, undefined, now)).to.equal(
        false,
      );
      expect(isLocalDateTimeInFuture('2026-07-09', '', now)).to.equal(false);
      expect(isLocalDateTimeInFuture('', '08:00:00', now)).to.equal(false);
    });

    it('returns false for invalid values', () => {
      expect(isLocalDateTimeInFuture('not-a-date', '08:00:00', now)).to.equal(
        false,
      );
    });

    it('compares the local date and time against the reference date', () => {
      expect(isLocalDateTimeInFuture('2026-07-08', '12:15:00', now)).to.equal(
        true,
      );
      expect(isLocalDateTimeInFuture('2026-07-08', '11:45:00', now)).to.equal(
        false,
      );
      expect(isLocalDateTimeInFuture('2026-07-09', '08:00', now)).to.equal(
        true,
      );
    });
  });

  describe('getTomorrowLocalDate', () => {
    it('returns the next day in Y-m-d format', () => {
      expect(getTomorrowLocalDate(new Date('2026-07-08T12:00:00'))).to.equal(
        '2026-07-09',
      );
      expect(getTomorrowLocalDate(new Date('2026-12-31T23:00:00'))).to.equal(
        '2027-01-01',
      );
    });
  });

  describe('getLocalTimeOfDayItems', () => {
    it('generates 15-minute steps for the whole day', () => {
      const items = getLocalTimeOfDayItems();
      const keys = Object.keys(items);
      expect(keys).to.have.length(96);
      expect(keys[0]).to.equal('00:00:00');
      expect(keys[1]).to.equal('00:15:00');
      expect(keys[95]).to.equal('23:45:00');
      expect(items['08:30:00']).to.equal('08:30');
    });
  });
});
