import { filtersToParam } from '../../../../assets/js/src/automation/listing/filters';

type Filter = {
  field: string;
  operator: string;
  value: unknown;
};

const toParam = (filters: Filter[]) =>
  filtersToParam(filters as never as Parameters<typeof filtersToParam>[0]);

describe('automation listing filtersToParam', () => {
  it('returns an empty object when there are no filters', () => {
    expect(filtersToParam(undefined)).to.deep.equal({});
    expect(toParam([])).to.deep.equal({});
  });

  it('drops filters with empty values', () => {
    expect(
      toParam([
        { field: 'trigger', operator: 'isAny', value: [] },
        { field: 'activity', operator: 'is', value: '' },
      ]),
    ).to.deep.equal({});
  });

  it('serializes the trigger multi-select filter', () => {
    expect(
      toParam([{ field: 'trigger', operator: 'isAny', value: ['a', 'b'] }]),
    ).to.deep.equal({ trigger: ['a', 'b'] });
  });

  it('serializes the activity filter', () => {
    expect(
      toParam([{ field: 'activity', operator: 'is', value: 'has' }]),
    ).to.deep.equal({ activity: 'has' });
  });

  it('maps created_at before/after operators to bounds', () => {
    expect(
      toParam([
        { field: 'created_at', operator: 'before', value: '2026-01-01' },
      ]),
    ).to.deep.equal({ created_before: '2026-01-01' });
    expect(
      toParam([
        { field: 'created_at', operator: 'after', value: '2026-01-01' },
      ]),
    ).to.deep.equal({ created_after: '2026-01-01' });
  });

  it('maps a created_at between range to both bounds', () => {
    expect(
      toParam([
        {
          field: 'created_at',
          operator: 'between',
          value: ['2026-01-01', '2026-02-01'],
        },
      ]),
    ).to.deep.equal({
      created_after: '2026-01-01',
      created_before: '2026-02-01',
    });
  });

  it('maps updated_at to its own bounds', () => {
    expect(
      toParam([
        {
          field: 'updated_at',
          operator: 'between',
          value: ['2026-01-01', '2026-02-01'],
        },
      ]),
    ).to.deep.equal({
      updated_after: '2026-01-01',
      updated_before: '2026-02-01',
    });
  });

  it('combines multiple filters', () => {
    expect(
      toParam([
        { field: 'trigger', operator: 'isAny', value: ['x'] },
        { field: 'activity', operator: 'is', value: 'none' },
        { field: 'created_at', operator: 'after', value: '2026-01-01' },
      ]),
    ).to.deep.equal({
      trigger: ['x'],
      activity: 'none',
      created_after: '2026-01-01',
    });
  });
});
