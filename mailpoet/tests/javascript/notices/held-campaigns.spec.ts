import { expect } from 'chai';
import {
  confirmCopy,
  getLateness,
  lateText,
  markBusy,
  clearBusy,
  nextFocusIndex,
  removeRow,
  rowActions,
  scheduleText,
  subjectText,
  visibleRows,
  VISIBLE_ROWS_LIMIT,
} from '../../../assets/js/src/notices/held-campaigns';

const HOUR = 3600;
const DAY = 24 * HOUR;

const row = (id: number, overrides: Partial<HeldCampaign> = {}) =>
  ({
    id,
    subject: `Email ${id}`,
    type: 'standard',
    scheduled_at: '2026-07-23 10:00:00',
    late_seconds: 25 * HOUR,
    parent_id: null,
    can_reschedule: true,
    can_edit_link: null,
    ...overrides,
  } as HeldCampaign);

const rows = (count: number) =>
  Array.from({ length: count }, (_, index) => row(index + 1));

describe('held campaigns notice helpers', () => {
  describe('getLateness', () => {
    it('uses minutes below one hour, at least one', () => {
      expect(getLateness(0)).to.deep.equal({ unit: 'minute', count: 1 });
      expect(getLateness(10 * 60)).to.deep.equal({
        unit: 'minute',
        count: 10,
      });
      expect(getLateness(59 * 60 + 59)).to.deep.equal({
        unit: 'minute',
        count: 59,
      });
    });

    it('uses hours from one hour up to two days', () => {
      expect(getLateness(HOUR)).to.deep.equal({ unit: 'hour', count: 1 });
      expect(getLateness(90 * 60)).to.deep.equal({ unit: 'hour', count: 1 });
      expect(getLateness(25 * HOUR)).to.deep.equal({ unit: 'hour', count: 25 });
      expect(getLateness(2 * DAY - 1)).to.deep.equal({
        unit: 'hour',
        count: 47,
      });
    });

    it('uses days from two days up', () => {
      expect(getLateness(2 * DAY)).to.deep.equal({ unit: 'day', count: 2 });
      expect(getLateness(88 * DAY)).to.deep.equal({ unit: 'day', count: 88 });
    });

    it('treats negative values as one minute', () => {
      expect(getLateness(-5)).to.deep.equal({ unit: 'minute', count: 1 });
    });
  });

  describe('lateText', () => {
    it('uses singular and plural forms', () => {
      expect(lateText(0)).to.equal('1 minute late');
      expect(lateText(10 * 60)).to.equal('10 minutes late');
      expect(lateText(HOUR)).to.equal('1 hour late');
      expect(lateText(25 * HOUR)).to.equal('25 hours late');
      expect(lateText(2 * DAY)).to.equal('2 days late');
    });
  });

  describe('scheduleText', () => {
    const format = (date: string) => `<${date}>`;

    it('shows the scheduled date and lateness', () => {
      expect(scheduleText(row(1), format)).to.equal(
        'Scheduled for <2026-07-23 10:00:00> · 25 hours late',
      );
    });

    it('shows queued and lateness without a scheduled date', () => {
      expect(
        scheduleText(
          row(1, { scheduled_at: null, late_seconds: 3 * DAY }),
          format,
        ),
      ).to.equal('Queued · 3 days late');
    });
  });

  describe('removeRow', () => {
    it('removes the row without mutating the input', () => {
      const input = rows(3);
      const result = removeRow(input, 2);
      expect(result.map((r) => r.id)).to.deep.equal([1, 3]);
      expect(input).to.have.length(3);
    });

    it('returns the same rows for an unknown id', () => {
      expect(removeRow(rows(2), 99).map((r) => r.id)).to.deep.equal([1, 2]);
    });
  });

  describe('visibleRows', () => {
    it('limits to the first five rows when collapsed', () => {
      expect(VISIBLE_ROWS_LIMIT).to.equal(5);
      expect(visibleRows(rows(8), false).map((r) => r.id)).to.deep.equal([
        1, 2, 3, 4, 5,
      ]);
    });

    it('shows five rows exactly without truncation', () => {
      expect(visibleRows(rows(5), false)).to.have.length(5);
    });

    it('shows all rows when expanded', () => {
      expect(visibleRows(rows(8), true)).to.have.length(8);
    });
  });

  describe('nextFocusIndex', () => {
    it('focuses the row that takes the removed row place', () => {
      expect(nextFocusIndex(rows(3), 1)).to.equal(1);
      expect(nextFocusIndex(rows(3), 0)).to.equal(0);
    });

    it('returns -1 (heading) when the last visible row is removed', () => {
      expect(nextFocusIndex(rows(3), 2)).to.equal(-1);
      expect(nextFocusIndex(rows(1), 0)).to.equal(-1);
    });

    it('works against visible rows when collapsed', () => {
      // 8 rows, 5 visible. Removing the 5th visible row: the 6th row slides in.
      const visible = visibleRows(rows(8), false);
      expect(nextFocusIndex(visible, 4, rows(8).length)).to.equal(4);
    });

    it('returns -1 when removing the last row of a collapsed list that has no more rows', () => {
      const all = rows(5);
      expect(nextFocusIndex(visibleRows(all, false), 4, all.length)).to.equal(
        -1,
      );
    });

    it('keeps the index in an expanded list after removal', () => {
      const all = rows(8);
      expect(nextFocusIndex(visibleRows(all, true), 7, all.length)).to.equal(
        -1,
      );
      expect(nextFocusIndex(visibleRows(all, true), 5, all.length)).to.equal(5);
    });
  });

  describe('rowActions', () => {
    it('offers send now, reschedule and cancel for standard emails', () => {
      expect(
        rowActions(row(1, { can_edit_link: 'http://x/send/1' })),
      ).to.deep.equal(['resume', 'reschedule', 'cancel']);
    });

    it('omits reschedule when there is no link to the send page', () => {
      expect(rowActions(row(1, { can_edit_link: null }))).to.deep.equal([
        'resume',
        'cancel',
      ]);
    });

    it('returns no actions for a row that is gone', () => {
      expect(rowActions(undefined)).to.deep.equal([]);
    });

    it('omits reschedule when the row cannot be rescheduled', () => {
      expect(
        rowActions(
          row(1, { type: 'notification_history', can_reschedule: false }),
        ),
      ).to.deep.equal(['resume', 'cancel']);
      expect(
        rowActions(row(1, { type: 're_engagement', can_reschedule: false })),
      ).to.deep.equal(['resume', 'cancel']);
    });
  });

  describe('busy rows', () => {
    it('tracks each row on its own', () => {
      let busy = markBusy({}, 1, 'resume');
      busy = markBusy(busy, 2, 'cancel');
      busy = clearBusy(busy, 1);
      expect(busy).to.deep.equal({ 2: 'cancel' });
    });

    it('does not mutate the input', () => {
      const input = markBusy({}, 1, 'resume');
      clearBusy(input, 1);
      expect(input).to.deep.equal({ 1: 'resume' });
    });
  });

  describe('subjectText', () => {
    it('falls back for an empty subject', () => {
      expect(subjectText('')).to.equal('(no subject)');
    });

    it('keeps a real subject', () => {
      expect(subjectText('Hello')).to.equal('Hello');
    });
  });

  describe('confirmCopy', () => {
    it('has distinct cancel copy per type', () => {
      const standard = confirmCopy('standard', 'cancel');
      const history = confirmCopy('notification_history', 'cancel');
      const reEngagement = confirmCopy('re_engagement', 'cancel');
      expect(standard).to.contain('drafts');
      expect(history).to.contain('deleted');
      expect(reEngagement).to.contain('skipped');
    });

    it('has reschedule copy', () => {
      expect(confirmCopy('standard', 'reschedule')).to.contain('drafts');
    });
  });
});
