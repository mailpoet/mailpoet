import type { Filter } from '@wordpress/dataviews';
import {
  SORT_FIELD_TO_ORDERBY,
  fromOrderby,
  getExactStatus,
  hasActiveFilters,
  requestFilterToViewFilters,
  toOrderby,
  viewFiltersToRequestFilter,
} from '../../../assets/js/src/subscribers/filters';

const f = (field: string, operator: string, value: unknown): Filter =>
  ({ field, operator, value } as Filter);

describe('Subscribers filters: view to request', () => {
  it('maps isAny and isNone for every set field', () => {
    expect(
      viewFiltersToRequestFilter([
        f('status', 'isAny', ['subscribed', 'bounced']),
        f('status', 'isNone', ['inactive']),
        f('segments', 'isAny', ['1']),
        f('tags', 'isNone', ['2']),
        f('source', 'isAny', ['form']),
      ]),
    ).to.deep.equal({
      status_in: ['subscribed', 'bounced'],
      status_not_in: ['inactive'],
      segment_in: [1],
      tag_not_in: [2],
      source_in: ['form'],
    });
  });

  it('maps isNone on segments, tags and source, and isAny on tags', () => {
    expect(
      viewFiltersToRequestFilter([
        f('segments', 'isNone', ['3']),
        f('tags', 'isAny', ['4']),
        f('source', 'isNone', ['api']),
      ]),
    ).to.deep.equal({
      segment_not_in: [3],
      tag_in: [4],
      source_not_in: ['api'],
    });
  });

  it('converts ids to integers and keeps without-list', () => {
    expect(
      viewFiltersToRequestFilter([
        f('segments', 'isAny', ['12', 'without-list']),
      ]),
    ).to.deep.equal({ segment_in: [12, 'without-list'] });
  });

  it('drops garbage ids and omits empty arrays', () => {
    expect(
      viewFiltersToRequestFilter([
        f('segments', 'isAny', ['abc', '0', '-3', '1.5', '']),
        f('tags', 'isAny', ['x', '5']),
        f('status', 'isAny', []),
        f('source', 'isAny', undefined),
      ]),
    ).to.deep.equal({ tag_in: [5] });
  });

  it('maps score operators', () => {
    expect(
      viewFiltersToRequestFilter([f('statistics', 'greaterThan', '2')]),
    ).to.deep.equal({ score_gt: 2 });
    expect(
      viewFiltersToRequestFilter([f('statistics', 'lessThan', 3.5)]),
    ).to.deep.equal({ score_lt: 3.5 });
    expect(
      viewFiltersToRequestFilter([f('statistics', 'between', ['1', 4])]),
    ).to.deep.equal({ score_min: 1, score_max: 4 });
  });

  it('drops invalid score values', () => {
    expect(
      viewFiltersToRequestFilter([f('statistics', 'greaterThan', '')]),
    ).to.deep.equal({});
    expect(
      viewFiltersToRequestFilter([f('statistics', 'between', ['', 'x'])]),
    ).to.deep.equal({});
  });

  it('drops score keys when tracking is off', () => {
    expect(
      viewFiltersToRequestFilter([f('statistics', 'greaterThan', 2)], {
        trackingEnabled: false,
      }),
    ).to.deep.equal({});
    expect(
      viewFiltersToRequestFilter([f('statistics', 'greaterThan', 2)], {
        trackingEnabled: true,
      }),
    ).to.deep.equal({ score_gt: 2 });
  });

  it('maps date operators for created and subscribed', () => {
    expect(
      viewFiltersToRequestFilter([f('created_at', 'on', '2025-03-10')]),
    ).to.deep.equal({ created_from: '2025-03-10', created_to: '2025-03-10' });
    expect(
      viewFiltersToRequestFilter([
        f('last_subscribed_at', 'between', ['2025-03-01', '2025-03-31']),
      ]),
    ).to.deep.equal({
      subscribed_from: '2025-03-01',
      subscribed_to: '2025-03-31',
    });
  });

  it('shifts exclusive before and after by one day', () => {
    expect(
      viewFiltersToRequestFilter([f('created_at', 'before', '2025-03-01')]),
    ).to.deep.equal({ created_to: '2025-02-28' });
    expect(
      viewFiltersToRequestFilter([f('created_at', 'after', '2025-03-01')]),
    ).to.deep.equal({ created_from: '2025-03-02' });
  });

  it('drops invalid dates', () => {
    expect(
      viewFiltersToRequestFilter([f('created_at', 'on', '2025-02-30')]),
    ).to.deep.equal({});
  });

  it('returns an empty object for undefined filters', () => {
    expect(viewFiltersToRequestFilter(undefined)).to.deep.equal({});
  });
});

describe('Subscribers filters: request to view', () => {
  it('maps sets to isAny and isNone with string values', () => {
    expect(
      requestFilterToViewFilters({
        status_in: ['subscribed'],
        status_not_in: ['bounced'],
        segment_in: [12, 'without-list'],
        tag_not_in: [3],
        source_in: ['form'],
      }),
    ).to.deep.equal([
      f('status', 'isAny', ['subscribed']),
      f('status', 'isNone', ['bounced']),
      f('segments', 'isAny', ['12', 'without-list']),
      f('tags', 'isNone', ['3']),
      f('source', 'isAny', ['form']),
    ]);
  });

  it('maps legacy segment and tag', () => {
    expect(requestFilterToViewFilters({ segment: 7, tag: '9' })).to.deep.equal([
      f('segments', 'isAny', ['7']),
      f('tags', 'isAny', ['9']),
    ]);
  });

  it('maps dates to inclusive operators', () => {
    expect(
      requestFilterToViewFilters({
        created_from: '2025-03-10',
        created_to: '2025-03-10',
      }),
    ).to.deep.equal([f('created_at', 'on', '2025-03-10')]);
    expect(
      requestFilterToViewFilters({
        subscribed_from: '2025-03-01',
        subscribed_to: '2025-03-31',
      }),
    ).to.deep.equal([
      f('last_subscribed_at', 'between', ['2025-03-01', '2025-03-31']),
    ]);
    expect(
      requestFilterToViewFilters({ created_from: '2025-03-01' }),
    ).to.deep.equal([f('created_at', 'after', '2025-02-28')]);
    expect(
      requestFilterToViewFilters({ created_to: '2025-03-01' }),
    ).to.deep.equal([f('created_at', 'before', '2025-03-02')]);
  });

  it('round-trips every offered date operator', () => {
    const cases: Array<[string, unknown]> = [
      ['on', '2025-03-10'],
      ['after', '2025-02-28'],
      ['after', '2024-02-28'],
      ['after', '2024-12-31'],
      ['before', '2025-03-01'],
      ['before', '2025-02-28'],
      ['before', '2024-02-28'],
      ['before', '2024-12-31'],
      ['between', ['2025-03-01', '2025-03-31']],
    ];
    ['created_at', 'last_subscribed_at'].forEach((field) => {
      cases.forEach(([operator, value]) => {
        const view = [f(field, operator, value)];
        expect(
          requestFilterToViewFilters(viewFiltersToRequestFilter(view)),
        ).to.deep.equal(view);
      });
    });
  });

  it('maps score keys', () => {
    expect(requestFilterToViewFilters({ score_gt: 2 })).to.deep.equal([
      f('statistics', 'greaterThan', 2),
    ]);
    expect(requestFilterToViewFilters({ score_lt: 4 })).to.deep.equal([
      f('statistics', 'lessThan', 4),
    ]);
    expect(
      requestFilterToViewFilters({ score_min: 1, score_max: 4 }),
    ).to.deep.equal([f('statistics', 'between', [1, 4])]);
  });

  it('drops score filters when tracking is off', () => {
    expect(
      requestFilterToViewFilters({ score_gt: 2 }, { trackingEnabled: false }),
    ).to.deep.equal([]);
  });

  it('ignores unknown keys, garbage and empty values', () => {
    expect(
      requestFilterToViewFilters({
        foo: 'bar',
        segment_in: [],
        tag_in: ['x'],
        status_in: ['nonsense'],
        created_from: 'nope',
      }),
    ).to.deep.equal([]);
  });

  it('round trips view to request to view', () => {
    const view: Filter[] = [
      f('status', 'isAny', ['subscribed']),
      f('segments', 'isNone', ['12', 'without-list']),
      f('tags', 'isAny', ['3']),
      f('source', 'isNone', ['form']),
      f('statistics', 'between', [1, 4]),
      f('last_subscribed_at', 'between', ['2025-03-01', '2025-03-31']),
      f('created_at', 'on', '2025-03-10'),
    ];
    const request = viewFiltersToRequestFilter(view);
    const back = requestFilterToViewFilters(request);
    expect(viewFiltersToRequestFilter(back)).to.deep.equal(request);
    expect(back).to.have.length(view.length);
  });

  it('keeps exclusive operators stable after one round trip', () => {
    const request = viewFiltersToRequestFilter([
      f('created_at', 'after', '2025-03-01'),
    ]);
    expect(
      viewFiltersToRequestFilter(requestFilterToViewFilters(request)),
    ).to.deep.equal(request);
  });
});

describe('Subscribers filters: helpers', () => {
  it('getExactStatus returns a single isAny status only', () => {
    expect(getExactStatus([f('status', 'isAny', ['unconfirmed'])])).to.equal(
      'unconfirmed',
    );
    expect(getExactStatus([f('status', 'isAny', ['a', 'b'])])).to.equal(null);
    expect(getExactStatus([f('status', 'isNone', ['bounced'])])).to.equal(null);
    expect(getExactStatus([f('tags', 'isAny', ['1'])])).to.equal(null);
    expect(getExactStatus([f('status', 'isAny', [])])).to.equal(null);
    expect(getExactStatus(undefined)).to.equal(null);
  });

  it('maps sort fields to orderby and back', () => {
    expect(SORT_FIELD_TO_ORDERBY).to.deep.equal({
      statistics: 'engagement_score',
    });
    expect(toOrderby('statistics')).to.equal('engagement_score');
    expect(toOrderby('email')).to.equal('email');
    expect(fromOrderby('engagement_score')).to.equal('statistics');
    expect(fromOrderby('created_at')).to.equal('created_at');
  });

  it('hasActiveFilters checks filters and search', () => {
    expect(hasActiveFilters([], '')).to.equal(false);
    expect(hasActiveFilters(undefined, undefined)).to.equal(false);
    expect(hasActiveFilters([], 'abc')).to.equal(true);
    expect(hasActiveFilters([f('status', 'isAny', ['x'])], '')).to.equal(true);
  });
});
