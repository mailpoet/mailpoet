import {
  isPausedOnSendPage,
  isScheduleInPast,
  withoutStaleSchedule,
} from '../../../../assets/js/src/newsletters/send/held-email';

const NOW = new Date('2026-07-24T12:00:00Z');

describe('send page held email helpers', () => {
  describe('isPausedOnSendPage', () => {
    it('is true for a manually paused sending email', () => {
      expect(
        isPausedOnSendPage('sending', { status: 'paused', pause_reason: null }),
      ).to.equal(true);
    });

    it('is false for a held email, so the normal send controls show', () => {
      expect(
        isPausedOnSendPage('sending', {
          status: 'paused',
          pause_reason: 'overdue',
        }),
      ).to.equal(false);
    });

    it('is false when not sending or not paused', () => {
      expect(isPausedOnSendPage('draft', undefined)).to.equal(false);
      expect(isPausedOnSendPage('sending', { status: 'running' })).to.equal(
        false,
      );
    });
  });

  describe('withoutStaleSchedule', () => {
    const defaults = {
      scheduledAt: '2026-07-25 06:00:00',
      scheduledLocalDate: '2026-07-25',
      scheduledLocalTime: '08:00:00',
    };

    it('turns scheduling off and replaces the old date with the default', () => {
      const options = {
        isScheduled: '1',
        scheduledAt: '2026-07-23 10:00:00',
        other: 'x',
      };
      const result = withoutStaleSchedule(options, defaults);
      expect(result).to.deep.equal({
        isScheduled: '0',
        scheduledAt: '2026-07-25 06:00:00',
        scheduledLocalDate: '',
        scheduledLocalTime: '',
        other: 'x',
      });
      expect(options.scheduledAt).to.equal('2026-07-23 10:00:00');
    });

    it('replaces the old subscriber time zone date and time too', () => {
      const result = withoutStaleSchedule(
        {
          isScheduled: '1',
          scheduleMode: 'subscriber_timezone',
          scheduledLocalDate: '2026-07-23',
          scheduledLocalTime: '10:00:00',
          other: 'x',
        },
        defaults,
      );
      expect(result).to.deep.equal({
        isScheduled: '0',
        scheduleMode: 'subscriber_timezone',
        scheduledAt: '2026-07-25 06:00:00',
        scheduledLocalDate: '2026-07-25',
        scheduledLocalTime: '08:00:00',
        other: 'x',
      });
    });
  });

  describe('isScheduleInPast', () => {
    const options = (scheduledAt: string, isScheduled = '1') => ({
      isScheduled,
      scheduledAt,
    });

    it('blocks a past date on a held email', () => {
      expect(
        isScheduleInPast(true, options('2026-07-24 11:59:59'), NOW),
      ).to.equal(true);
    });

    it('allows a future date', () => {
      expect(
        isScheduleInPast(true, options('2026-07-24 12:00:01'), NOW),
      ).to.equal(false);
    });

    it('allows any date when scheduling is off', () => {
      expect(
        isScheduleInPast(true, options('2026-07-01 10:00:00', '0'), NOW),
      ).to.equal(false);
    });

    it('does not apply to emails that were not held', () => {
      expect(
        isScheduleInPast(false, options('2026-07-01 10:00:00'), NOW),
      ).to.equal(false);
    });

    it('ignores subscriber time zone sends and missing dates', () => {
      expect(
        isScheduleInPast(
          true,
          {
            ...options('2026-07-01 10:00:00'),
            scheduleMode: 'subscriber_timezone',
          },
          NOW,
        ),
      ).to.equal(false);
      expect(isScheduleInPast(true, { isScheduled: '1' }, NOW)).to.equal(false);
    });
  });
});
