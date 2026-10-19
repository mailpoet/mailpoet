import { expect } from 'chai';
import { resetLocaleData, setLocaleData } from '@wordpress/i18n';
import {
  actionLabel,
  actionScreenReaderText,
  applyBulkResult,
  bulkCancelCopy,
  bulkResumeMessage,
  bulkScreenReaderText,
  bulkSummary,
  confirmButtonLabel,
  confirmCopy,
  showBulkActions,
  getLateness,
  lateText,
  markBusy,
  clearBusy,
  nextFocusIndex,
  removeRow,
  rowActions,
  scheduleText,
  subjectText,
  unsentAnnouncement,
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
      const standard = confirmCopy('standard');
      const history = confirmCopy('notification_history');
      const reEngagement = confirmCopy('re_engagement');
      expect(standard).to.contain('drafts');
      expect(history).to.contain('deleted');
      expect(reEngagement).to.contain('skipped');
    });
  });

  describe('button copy', () => {
    it('labels row buttons, with an ellipsis where a dialog follows', () => {
      expect(actionLabel('resume')).to.equal('Send now');
      expect(actionLabel('reschedule')).to.equal('Reschedule\u2026');
      expect(actionLabel('cancel')).to.equal('Don\u2019t send\u2026');
    });

    it('labels the dialog confirm button without an ellipsis', () => {
      expect(confirmButtonLabel()).to.equal('Don\u2019t send');
    });

    it('names buttons for screen readers without the ellipsis', () => {
      expect(actionScreenReaderText('resume', 'Hello')).to.equal(
        'Send now: Hello',
      );
      expect(actionScreenReaderText('reschedule', 'Hello')).to.equal(
        'Reschedule: Hello',
      );
      expect(actionScreenReaderText('cancel', 'Hello')).to.equal(
        'Don\u2019t send: Hello',
      );
    });

    it('announces an email that will not be sent', () => {
      expect(unsentAnnouncement('Hello')).to.equal('Won\u2019t be sent: Hello');
    });
  });

  describe('bulk actions', () => {
    it('shows bulk buttons only for two or more held emails', () => {
      expect(showBulkActions(0)).to.equal(false);
      expect(showBulkActions(1)).to.equal(false);
      expect(showBulkActions(2)).to.equal(true);
    });

    it('words the send all confirmation with the count', () => {
      expect(bulkResumeMessage(2)).to.equal(
        '2 emails will be sent to their lists now.',
      );
      expect(bulkResumeMessage(1)).to.equal(
        '1 email will be sent to its lists now.',
      );
    });

    it('describes only the types that are held', () => {
      const standard = bulkCancelCopy(['standard']);
      expect(standard).to.contain('drafts');
      expect(standard).not.to.contain('deleted');
      expect(standard).not.to.contain('skipped');
      expect(bulkCancelCopy(['notification_history'])).to.contain('deleted');
      expect(bulkCancelCopy(['re_engagement'])).to.contain('skipped');
      const all = bulkCancelCopy([
        'standard',
        'notification_history',
        're_engagement',
        'standard',
      ]);
      expect(all).to.contain('drafts');
      expect(all).to.contain('deleted');
      expect(all).to.contain('skipped');
    });

    it('names bulk buttons for screen readers without the ellipsis', () => {
      expect(bulkScreenReaderText('resume')).to.equal('Send all held emails');
      expect(bulkScreenReaderText('cancel')).to.equal(
        'Don\u2019t send any held email',
      );
    });

    it('removes done rows and keeps failed rows', () => {
      const result = applyBulkResult(rows(4), {
        done: [1, 3],
        failed: [{ id: 2, message: 'Nope' }],
        skipped: [],
      });
      expect(result.map((r) => r.id)).to.deep.equal([2, 4]);
    });

    it('removes skipped rows because they were handled elsewhere', () => {
      const result = applyBulkResult(rows(4), {
        done: [1],
        failed: [{ id: 2, message: 'Nope' }],
        skipped: [3],
      });
      expect(result.map((r) => r.id)).to.deep.equal([2, 4]);
    });

    it('summarises a bulk send', () => {
      expect(bulkSummary('resume', 3, 0)).to.equal('3 emails sent');
      expect(bulkSummary('resume', 1, 0)).to.equal('1 email sent');
      expect(bulkSummary('resume', 3, 1)).to.equal(
        '3 emails sent, 1 couldn\u2019t be sent',
      );
      expect(bulkSummary('resume', 0, 2)).to.equal('2 couldn\u2019t be sent');
    });

    describe('failed count plural forms', () => {
      afterEach(() => resetLocaleData());

      it('lets translators pick the plural form of the failed count', () => {
        setLocaleData(
          {
            '%d couldn\u2019t be sent': ['%d: one', '%d: many'],
            '%d couldn\u2019t be updated': ['%d: one', '%d: many'],
          } as unknown as Record<string, string[]>,
          'mailpoet',
        );
        expect(bulkSummary('resume', 0, 1)).to.equal('1: one');
        expect(bulkSummary('resume', 0, 3)).to.equal('3: many');
        expect(bulkSummary('cancel', 0, 1)).to.equal('1: one');
        expect(bulkSummary('cancel', 0, 3)).to.equal('3: many');
      });
    });

    it('mentions emails that were already handled', () => {
      expect(bulkSummary('resume', 0, 0, 2)).to.equal('2 already handled');
      expect(bulkSummary('cancel', 0, 0, 1)).to.equal('1 already handled');
      expect(bulkSummary('resume', 2, 1, 3)).to.equal(
        '2 emails sent, 1 couldn\u2019t be sent, 3 already handled',
      );
      expect(bulkSummary('resume', 0, 0, 0)).to.equal('');
    });

    it('summarises a bulk cancel', () => {
      expect(bulkSummary('cancel', 2, 0)).to.equal(
        '2 emails won\u2019t be sent',
      );
      expect(bulkSummary('cancel', 1, 0)).to.equal(
        '1 email won\u2019t be sent',
      );
      expect(bulkSummary('cancel', 2, 1)).to.equal(
        '2 emails won\u2019t be sent, 1 couldn\u2019t be updated',
      );
      expect(bulkSummary('cancel', 0, 1)).to.equal(
        '1 couldn\u2019t be updated',
      );
    });
  });
});
