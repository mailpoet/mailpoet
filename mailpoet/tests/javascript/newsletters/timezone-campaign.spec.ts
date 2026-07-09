import {
  formatTimezoneLabel,
  getBatchStatusLabel,
  getScheduledWindow,
  getTimezoneBreakdown,
  hasStartedTimezoneBatches,
  isTimezoneCampaignQueue,
} from '../../../assets/js/src/newsletters/timezone-campaign';

const timezoneQueue = (meta: Record<string, unknown>) => ({
  meta: { sendByTimezone: true, ...meta },
});

const breakdownEntry = (overrides: Record<string, unknown> = {}) => ({
  timezone: 'Europe/Prague',
  fallback_used: false,
  scheduled_at: '2026-07-20 09:00:00',
  status: 'scheduled',
  in_progress: false,
  count_total: 10,
  count_processed: 0,
  count_to_process: 10,
  ...overrides,
});

describe('timezone campaign helpers', () => {
  describe('isTimezoneCampaignQueue', () => {
    it('returns true when the queue meta has the sendByTimezone flag', () => {
      expect(isTimezoneCampaignQueue(timezoneQueue({}))).to.equal(true);
    });

    it('returns false for regular queues', () => {
      expect(isTimezoneCampaignQueue({ meta: {} })).to.equal(false);
      expect(isTimezoneCampaignQueue({ meta: null })).to.equal(false);
      expect(isTimezoneCampaignQueue({})).to.equal(false);
    });

    it('returns false for the legacy BC shapes without a queue', () => {
      expect(isTimezoneCampaignQueue(false)).to.equal(false);
      expect(isTimezoneCampaignQueue(null)).to.equal(false);
      expect(isTimezoneCampaignQueue(undefined)).to.equal(false);
    });
  });

  describe('getTimezoneBreakdown', () => {
    it('parses breakdown entries from the aggregate queue meta', () => {
      const queue = timezoneQueue({
        timezoneBreakdown: [
          breakdownEntry(),
          breakdownEntry({
            timezone: 'America/New_York',
            fallback_used: true,
            status: null,
            count_processed: 3,
          }),
        ],
      });
      expect(getTimezoneBreakdown(queue)).to.deep.equal([
        {
          timezone: 'Europe/Prague',
          fallbackUsed: false,
          scheduledAt: '2026-07-20 09:00:00',
          status: 'scheduled',
          inProgress: false,
          countTotal: 10,
          countProcessed: 0,
          countToProcess: 10,
        },
        {
          timezone: 'America/New_York',
          fallbackUsed: true,
          scheduledAt: '2026-07-20 09:00:00',
          status: null,
          inProgress: false,
          countTotal: 10,
          countProcessed: 3,
          countToProcess: 10,
        },
      ]);
    });

    it('coerces numeric strings and invalid counts', () => {
      const queue = timezoneQueue({
        timezoneBreakdown: [
          breakdownEntry({
            count_total: '25',
            count_processed: 'oops',
            count_to_process: -5,
          }),
        ],
      });
      const [entry] = getTimezoneBreakdown(queue);
      expect(entry.countTotal).to.equal(25);
      expect(entry.countProcessed).to.equal(0);
      expect(entry.countToProcess).to.equal(0);
    });

    it('skips entries that are not objects', () => {
      const queue = timezoneQueue({
        timezoneBreakdown: [null, 'bogus', 42, breakdownEntry()],
      });
      expect(getTimezoneBreakdown(queue)).to.have.length(1);
    });

    it('returns an empty list when the breakdown is missing or invalid', () => {
      expect(getTimezoneBreakdown(timezoneQueue({}))).to.deep.equal([]);
      expect(
        getTimezoneBreakdown(timezoneQueue({ timezoneBreakdown: 'bogus' })),
      ).to.deep.equal([]);
      expect(getTimezoneBreakdown(false)).to.deep.equal([]);
    });
  });

  describe('getScheduledWindow', () => {
    it('returns the first and last scheduled dates', () => {
      const queue = timezoneQueue({
        firstScheduledAt: '2026-07-20 07:00:00',
        lastScheduledAt: '2026-07-21 06:00:00',
      });
      expect(getScheduledWindow(queue)).to.deep.equal({
        first: '2026-07-20 07:00:00',
        last: '2026-07-21 06:00:00',
      });
    });

    it('returns null when either boundary is missing', () => {
      expect(
        getScheduledWindow(timezoneQueue({ firstScheduledAt: '2026-07-20' })),
      ).to.equal(null);
      expect(
        getScheduledWindow(timezoneQueue({ lastScheduledAt: '2026-07-21' })),
      ).to.equal(null);
      expect(getScheduledWindow(false)).to.equal(null);
    });
  });

  describe('getBatchStatusLabel', () => {
    it('treats a null status as running (a batch is actively sending)', () => {
      expect(getBatchStatusLabel(null)).to.equal('Sending');
    });

    it('maps the known task statuses', () => {
      expect(getBatchStatusLabel('scheduled')).to.equal('Scheduled');
      expect(getBatchStatusLabel('paused')).to.equal('Paused');
      expect(getBatchStatusLabel('completed')).to.equal('Sent');
      expect(getBatchStatusLabel('cancelled')).to.equal('Cancelled');
      expect(getBatchStatusLabel('invalid')).to.equal('Failed');
    });

    it('passes unknown statuses through unchanged', () => {
      expect(getBatchStatusLabel('something_new')).to.equal('something_new');
    });
  });

  describe('formatTimezoneLabel', () => {
    it('returns the time zone name as-is', () => {
      expect(formatTimezoneLabel('Europe/Prague', false)).to.equal(
        'Europe/Prague',
      );
    });

    it('marks fallback groups with the site default suffix', () => {
      expect(formatTimezoneLabel('Europe/Prague', true)).to.equal(
        'Europe/Prague (site default)',
      );
    });

    it('labels a missing time zone as unknown', () => {
      expect(formatTimezoneLabel(null, false)).to.equal('Unknown');
    });
  });

  describe('hasStartedTimezoneBatches', () => {
    it('returns false while every batch is scheduled or paused and untouched', () => {
      const queue = timezoneQueue({
        timezoneBreakdown: [
          breakdownEntry(),
          breakdownEntry({ status: 'paused' }),
        ],
      });
      expect(hasStartedTimezoneBatches(queue)).to.equal(false);
    });

    it('returns true when a batch is running (null status)', () => {
      const queue = timezoneQueue({
        timezoneBreakdown: [breakdownEntry({ status: null })],
      });
      expect(hasStartedTimezoneBatches(queue)).to.equal(true);
    });

    it('returns true when a batch has processed subscribers', () => {
      const queue = timezoneQueue({
        timezoneBreakdown: [breakdownEntry({ count_processed: 1 })],
      });
      expect(hasStartedTimezoneBatches(queue)).to.equal(true);
    });

    it('treats a batch picked up by a worker as started', () => {
      const queue = timezoneQueue({
        timezoneBreakdown: [breakdownEntry({ in_progress: true })],
      });
      expect(hasStartedTimezoneBatches(queue)).to.equal(true);
    });

    it('returns true when a batch is completed', () => {
      const queue = timezoneQueue({
        timezoneBreakdown: [
          breakdownEntry(),
          breakdownEntry({ status: 'completed', count_processed: 10 }),
        ],
      });
      expect(hasStartedTimezoneBatches(queue)).to.equal(true);
    });

    it('returns false when there is no breakdown to inspect', () => {
      expect(hasStartedTimezoneBatches(timezoneQueue({}))).to.equal(false);
      expect(hasStartedTimezoneBatches(false)).to.equal(false);
    });
  });
});
