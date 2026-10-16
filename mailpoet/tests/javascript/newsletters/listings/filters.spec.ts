import type { Filter } from '@wordpress/dataviews';
import {
  getAllowedStatuses,
  hashFilterToRequestFilter,
  requestFilterToHashFilter,
  requestFilterToViewFilters,
  sanitizeSort,
  sendingStatusHashFilterToRequestFilter,
  sendingStatusRequestFilterToHashFilter,
  sendingStatusRequestFilterToViewFilters,
  sendingStatusViewFiltersToRequestFilter,
  viewFiltersToRequestFilter,
} from '../../../../assets/js/src/newsletters/listings/filters';
import { SENT_AT_OPERATORS } from '../../../../assets/js/src/newsletters/listings/listing-fields';
import {
  buildHash,
  parseHash,
} from '../../../../assets/js/src/newsletters/listings/hash-state';

const standardStatuses = ['draft', 'scheduled', 'sending', 'paused', 'sent'];

describe('newsletter listing filters', () => {
  describe('getAllowedStatuses', () => {
    it('returns the statuses of each type', () => {
      expect(getAllowedStatuses('standard')).to.deep.equal(standardStatuses);
      expect(getAllowedStatuses('notification_history')).to.deep.equal([
        'sending',
        'paused',
        'sent',
      ]);
      expect(getAllowedStatuses('notification')).to.deep.equal([
        'active',
        'draft',
      ]);
      expect(getAllowedStatuses('re_engagement')).to.deep.equal([
        'active',
        'draft',
      ]);
    });

    it('returns nothing for types without a status filter', () => {
      expect(getAllowedStatuses('welcome')).to.deep.equal([]);
      expect(getAllowedStatuses('automatic')).to.deep.equal([]);
    });
  });

  describe('viewFiltersToRequestFilter', () => {
    it('maps status, lists and dates', () => {
      expect(
        viewFiltersToRequestFilter(
          [
            { field: 'status', operator: 'isAny', value: ['draft', 'sent'] },
            { field: 'segments', operator: 'isNone', value: [1, '2'] },
            {
              field: 'sent_at',
              operator: 'between',
              value: ['2026-01-01', '2026-01-31'],
            },
          ],
          standardStatuses,
        ),
      ).to.deep.equal({
        status: ['draft', 'sent'],
        segment_ids: ['1', '2'],
        segment_operator: 'isNone',
        sent_at_from: '2026-01-01',
        sent_at_to: '2026-01-31',
      });
    });

    it('maps isAny lists', () => {
      expect(
        viewFiltersToRequestFilter(
          [{ field: 'segments', operator: 'isAny', value: ['4'] }],
          [],
        ),
      ).to.deep.equal({ segment_ids: ['4'], segment_operator: 'isAny' });
    });

    it('keeps only allowed statuses and omits the key when none remain', () => {
      expect(
        viewFiltersToRequestFilter(
          [{ field: 'status', operator: 'isAny', value: ['draft', 'bogus'] }],
          ['draft'],
        ),
      ).to.deep.equal({ status: ['draft'] });
      expect(
        viewFiltersToRequestFilter(
          [{ field: 'status', operator: 'isAny', value: ['sent'] }],
          ['active', 'draft'],
        ),
      ).to.deep.equal({});
      expect(
        viewFiltersToRequestFilter(
          [{ field: 'status', operator: 'isAny', value: ['draft'] }],
          [],
        ),
      ).to.deep.equal({});
    });

    it('drops non-digit list ids', () => {
      expect(
        viewFiltersToRequestFilter(
          [{ field: 'segments', operator: 'isAny', value: ['1', 'x', ''] }],
          [],
        ),
      ).to.deep.equal({ segment_ids: ['1'], segment_operator: 'isAny' });
      expect(
        viewFiltersToRequestFilter(
          [{ field: 'segments', operator: 'isAny', value: ['x'] }],
          [],
        ),
      ).to.deep.equal({});
    });

    it('caps status and list sets at 100 values', () => {
      const ids = Array.from({ length: 150 }, (_, index) => String(index + 1));
      const request = viewFiltersToRequestFilter(
        [{ field: 'segments', operator: 'isAny', value: ids }],
        [],
      );
      expect(request.segment_ids).to.have.length(100);
      expect(request.segment_ids?.[99]).to.equal('100');
    });

    it('maps single-bound date operators', () => {
      expect(
        viewFiltersToRequestFilter(
          [{ field: 'sent_at', operator: 'afterInc', value: '2026-02-01' }],
          [],
        ),
      ).to.deep.equal({ sent_at_from: '2026-02-01' });
      expect(
        viewFiltersToRequestFilter(
          [{ field: 'sent_at', operator: 'before', value: '2026-02-01' }],
          [],
        ),
      ).to.deep.equal({ sent_at_to: '2026-01-31' });
    });

    it('drops an invalid date', () => {
      expect(
        viewFiltersToRequestFilter(
          [{ field: 'sent_at', operator: 'on', value: 'nope' }],
          [],
        ),
      ).to.deep.equal({});
    });

    it('swaps a reversed date range', () => {
      expect(
        viewFiltersToRequestFilter(
          [
            {
              field: 'sent_at',
              operator: 'between',
              value: ['2026-03-01', '2026-02-01'],
            },
          ],
          [],
        ),
      ).to.deep.equal({
        sent_at_from: '2026-02-01',
        sent_at_to: '2026-03-01',
      });
    });

    it('yields no request filter for filters without a value yet', () => {
      expect(
        viewFiltersToRequestFilter(
          [
            { field: 'segments', operator: 'isAny', value: [] },
            { field: 'status', operator: 'isAny', value: undefined },
          ],
          standardStatuses,
        ),
      ).to.deep.equal({});
    });

    it('handles undefined filters', () => {
      expect(viewFiltersToRequestFilter(undefined, [])).to.deep.equal({});
    });
  });

  describe('requestFilterToViewFilters', () => {
    it('seeds status, lists and a between range', () => {
      expect(
        requestFilterToViewFilters({
          status: ['draft'],
          segment_ids: ['1', '2'],
          segment_operator: 'isNone',
          sent_at_from: '2026-01-01',
          sent_at_to: '2026-01-31',
        }),
      ).to.deep.equal([
        { field: 'status', operator: 'isAny', value: ['draft'] },
        { field: 'segments', operator: 'isNone', value: ['1', '2'] },
        {
          field: 'sent_at',
          operator: 'between',
          value: ['2026-01-01', '2026-01-31'],
        },
      ]);
    });

    it('seeds on for equal bounds and exclusive operators for one bound', () => {
      expect(
        requestFilterToViewFilters({
          sent_at_from: '2026-01-05',
          sent_at_to: '2026-01-05',
        }),
      ).to.deep.equal([
        { field: 'sent_at', operator: 'on', value: '2026-01-05' },
      ]);
      expect(
        requestFilterToViewFilters({ sent_at_from: '2026-01-05' }),
      ).to.deep.equal([
        { field: 'sent_at', operator: 'after', value: '2026-01-04' },
      ]);
      expect(
        requestFilterToViewFilters({ sent_at_to: '2026-01-05' }),
      ).to.deep.equal([
        { field: 'sent_at', operator: 'before', value: '2026-01-06' },
      ]);
    });

    it('shifts across month, year and leap day boundaries', () => {
      expect(
        requestFilterToViewFilters({ sent_at_from: '2026-01-01' }),
      ).to.deep.equal([
        { field: 'sent_at', operator: 'after', value: '2025-12-31' },
      ]);
      expect(
        requestFilterToViewFilters({ sent_at_to: '2025-12-31' }),
      ).to.deep.equal([
        { field: 'sent_at', operator: 'before', value: '2026-01-01' },
      ]);
      expect(
        requestFilterToViewFilters({ sent_at_to: '2024-02-28' }),
      ).to.deep.equal([
        { field: 'sent_at', operator: 'before', value: '2024-02-29' },
      ]);
      expect(
        requestFilterToViewFilters({ sent_at_from: '2024-03-01' }),
      ).to.deep.equal([
        { field: 'sent_at', operator: 'after', value: '2024-02-29' },
      ]);
    });

    it('defaults the list operator to isAny and returns [] for empty', () => {
      expect(requestFilterToViewFilters({ segment_ids: ['3'] })).to.deep.equal([
        { field: 'segments', operator: 'isAny', value: ['3'] },
      ]);
      expect(requestFilterToViewFilters({})).to.deep.equal([]);
    });

    it('round-trips with viewFiltersToRequestFilter', () => {
      const request = {
        status: ['sent'],
        segment_ids: ['9'],
        segment_operator: 'isNone' as const,
        sent_at_from: '2026-01-01',
      };
      expect(
        viewFiltersToRequestFilter(
          requestFilterToViewFilters(request),
          standardStatuses,
        ),
      ).to.deep.equal(request);
    });
  });

  describe('hashFilterToRequestFilter', () => {
    it('parses every key', () => {
      expect(
        hashFilterToRequestFilter(
          {
            status: 'draft,scheduled',
            segment: '1,2',
            segment_op: 'isNone',
            sent_from: '2026-01-01',
            sent_to: '2026-01-31',
          },
          standardStatuses,
        ),
      ).to.deep.equal({
        status: ['draft', 'scheduled'],
        segment_ids: ['1', '2'],
        segment_operator: 'isNone',
        sent_at_from: '2026-01-01',
        sent_at_to: '2026-01-31',
      });
    });

    it('treats a legacy single segment as isAny', () => {
      expect(
        hashFilterToRequestFilter({ segment: '3' }, standardStatuses),
      ).to.deep.equal({ segment_ids: ['3'], segment_operator: 'isAny' });
    });

    it('keeps a digit id even if the list no longer exists', () => {
      expect(hashFilterToRequestFilter({ segment: '99999' }, [])).to.deep.equal(
        { segment_ids: ['99999'], segment_operator: 'isAny' },
      );
    });

    it('drops invalid values silently', () => {
      expect(
        hashFilterToRequestFilter(
          {
            status: 'bogus',
            segment: 'a,1b',
            segment_op: 'isNone',
            sent_from: '2026-13-01',
            sent_to: 'x',
          },
          standardStatuses,
        ),
      ).to.deep.equal({});
    });

    it('drops an unknown segment_op and keeps the ids as isAny', () => {
      expect(
        hashFilterToRequestFilter({ segment: '1', segment_op: 'weird' }, []),
      ).to.deep.equal({ segment_ids: ['1'], segment_operator: 'isAny' });
    });

    it('drops statuses not allowed for the type or group', () => {
      expect(
        hashFilterToRequestFilter({ status: 'sent,active' }, [
          'active',
          'draft',
        ]),
      ).to.deep.equal({ status: ['active'] });
      expect(hashFilterToRequestFilter({ status: 'draft' }, [])).to.deep.equal(
        {},
      );
    });

    it('swaps the date range when from is after to', () => {
      expect(
        hashFilterToRequestFilter(
          { sent_from: '2026-02-01', sent_to: '2026-01-01' },
          [],
        ),
      ).to.deep.equal({
        sent_at_from: '2026-01-01',
        sent_at_to: '2026-02-01',
      });
    });

    it('caps the status and list sets at 100 values', () => {
      const ids = Array.from({ length: 150 }, (_, index) => index + 1);
      const request = hashFilterToRequestFilter({ segment: ids.join(',') }, []);
      expect(request.segment_ids).to.have.length(100);
    });

    it('keeps a valid single bound', () => {
      expect(
        hashFilterToRequestFilter({ sent_to: '2026-02-01' }, []),
      ).to.deep.equal({ sent_at_to: '2026-02-01' });
    });
  });

  describe('requestFilterToHashFilter', () => {
    it('serializes every key, with segment_op only for isNone', () => {
      expect(
        requestFilterToHashFilter({
          status: ['draft', 'sent'],
          segment_ids: ['1', '2'],
          segment_operator: 'isNone',
          sent_at_from: '2026-01-01',
          sent_at_to: '2026-01-31',
        }),
      ).to.deep.equal({
        status: 'draft,sent',
        segment: '1,2',
        segment_op: 'isNone',
        sent_from: '2026-01-01',
        sent_to: '2026-01-31',
      });
      expect(
        requestFilterToHashFilter({
          segment_ids: ['1'],
          segment_operator: 'isAny',
        }),
      ).to.deep.equal({ segment: '1' });
    });

    it('returns an empty object for an empty filter', () => {
      expect(requestFilterToHashFilter({})).to.deep.equal({});
    });

    it('round-trips every offered date operator through the request and hash', () => {
      const cases: Record<string, Filter[]> = {
        on: [
          { field: 'sent_at', operator: 'on', value: '2026-01-05' },
          { field: 'sent_at', operator: 'on', value: '2024-02-29' },
          { field: 'sent_at', operator: 'on', value: '2026-01-01' },
        ],
        before: [
          { field: 'sent_at', operator: 'before', value: '2026-01-05' },
          { field: 'sent_at', operator: 'before', value: '2026-01-01' },
          { field: 'sent_at', operator: 'before', value: '2024-03-01' },
          { field: 'sent_at', operator: 'before', value: '2025-12-31' },
        ],
        after: [
          { field: 'sent_at', operator: 'after', value: '2026-01-05' },
          { field: 'sent_at', operator: 'after', value: '2025-12-31' },
          { field: 'sent_at', operator: 'after', value: '2024-02-28' },
          { field: 'sent_at', operator: 'after', value: '2024-02-29' },
        ],
        between: [
          {
            field: 'sent_at',
            operator: 'between',
            value: ['2026-01-01', '2026-01-31'],
          },
          {
            field: 'sent_at',
            operator: 'between',
            value: ['2023-12-31', '2024-02-29'],
          },
        ],
      };
      expect(Object.keys(cases).sort()).to.deep.equal(
        [...SENT_AT_OPERATORS].sort(),
      );
      Object.values(cases)
        .flat()
        .forEach((view) => {
          const request = viewFiltersToRequestFilter([view], []);
          const hash = requestFilterToHashFilter(request);
          expect(
            requestFilterToViewFilters(hashFilterToRequestFilter(hash, [])),
          ).to.deep.equal([view]);
        });
    });

    it('round-trips comma lists through buildHash and parseHash', () => {
      const request = {
        status: ['draft', 'scheduled'],
        segment_ids: ['1', '2'],
        segment_operator: 'isNone' as const,
        sent_at_from: '2026-01-01',
      };
      const hash = buildHash(
        'standard',
        'all',
        {},
        requestFilterToHashFilter(request),
        { sort: 'sent_at', order: 'desc', perPage: 20 },
      );
      expect(hash).to.contain('%2C');
      const parsed = parseHash(hash, 'standard', ['all', 'trash']);
      expect(
        hashFilterToRequestFilter(parsed.filter ?? {}, standardStatuses),
      ).to.deep.equal(request);
    });
  });

  describe('sending status filters', () => {
    it('maps the result multi-select', () => {
      expect(
        sendingStatusViewFiltersToRequestFilter([
          { field: 'failed', operator: 'isAny', value: ['failed', 'sent'] },
        ]),
      ).to.deep.equal({ result: ['failed', 'sent'] });
    });

    it('keeps only known result values', () => {
      expect(
        sendingStatusViewFiltersToRequestFilter([
          { field: 'failed', operator: 'isAny', value: ['failed', 'x'] },
        ]),
      ).to.deep.equal({ result: ['failed'] });
      expect(
        sendingStatusViewFiltersToRequestFilter([
          { field: 'failed', operator: 'isAny', value: ['x'] },
        ]),
      ).to.deep.equal({});
      expect(sendingStatusViewFiltersToRequestFilter(undefined)).to.deep.equal(
        {},
      );
    });

    it('seeds view filters', () => {
      expect(
        sendingStatusRequestFilterToViewFilters({ result: ['unprocessed'] }),
      ).to.deep.equal([
        { field: 'failed', operator: 'isAny', value: ['unprocessed'] },
      ]);
      expect(sendingStatusRequestFilterToViewFilters({})).to.deep.equal([]);
    });

    it('parses the hash result', () => {
      expect(
        sendingStatusHashFilterToRequestFilter({
          result: 'failed,unprocessed',
        }),
      ).to.deep.equal({ result: ['failed', 'unprocessed'] });
    });

    it('drops an invalid hash result', () => {
      expect(
        sendingStatusHashFilterToRequestFilter({ result: 'nope' }),
      ).to.deep.equal({});
    });

    it('serializes to the hash', () => {
      expect(
        sendingStatusRequestFilterToHashFilter({
          result: ['failed', 'unprocessed'],
        }),
      ).to.deep.equal({ result: 'failed,unprocessed' });
      expect(sendingStatusRequestFilterToHashFilter({})).to.deep.equal({});
    });
  });

  describe('sanitizeSort', () => {
    const fallback = { field: 'sent_at', direction: 'desc' as const };

    it('keeps a sortable field', () => {
      const sort = { field: 'name', direction: 'asc' as const };
      expect(sanitizeSort(sort, ['name', 'sent_at'], fallback)).to.equal(sort);
    });

    it('falls back for unknown fields and missing sort', () => {
      expect(
        sanitizeSort({ field: 'bogus', direction: 'asc' }, ['name'], fallback),
      ).to.equal(fallback);
      expect(sanitizeSort(undefined, ['name'], fallback)).to.equal(fallback);
    });
  });

  describe('sort fallback', () => {
    const fallback = { field: 'sent_at', direction: 'desc' as const };

    it('falls back for fields the listing has no sortable column for', () => {
      ['updated_at', 'created_at', 'id', 'status'].forEach((field) => {
        expect(
          sanitizeSort(
            { field, direction: 'asc' },
            ['name', 'sent_at'],
            fallback,
          ),
        ).to.equal(fallback);
      });
    });
  });
});
