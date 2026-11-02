import { expect } from 'chai';
import type { Filter } from '@wordpress/dataviews';

import {
  getActiveStatusTab,
  getSingleFilterValue,
  withStatusTab,
} from '../../../../assets/js/src/common/dataviews/status-tabs';

const listFilter: Filter = {
  field: 'segments',
  operator: 'isAny',
  value: ['3'],
};

describe('getSingleFilterValue', () => {
  it('returns the value of an "is any" filter with one value', () => {
    expect(
      getSingleFilterValue(
        [{ field: 'status', operator: 'isAny', value: ['unconfirmed'] }],
        'status',
      ),
    ).to.equal('unconfirmed');
  });

  it('returns null for several values, "is none" or no filter', () => {
    expect(
      getSingleFilterValue(
        [{ field: 'status', operator: 'isAny', value: ['a', 'b'] }],
        'status',
      ),
    ).to.equal(null);
    expect(
      getSingleFilterValue(
        [{ field: 'status', operator: 'isNone', value: ['a'] }],
        'status',
      ),
    ).to.equal(null);
    expect(
      getSingleFilterValue(
        [{ field: 'status', operator: 'isAny', value: [] }],
        'status',
      ),
    ).to.equal(null);
    expect(getSingleFilterValue([listFilter], 'status')).to.equal(null);
    expect(getSingleFilterValue(undefined, 'status')).to.equal(null);
  });
});

describe('getActiveStatusTab', () => {
  const statuses = ['subscribed', 'unconfirmed'];

  it('marks the status tab when the filter is that one status', () => {
    expect(
      getActiveStatusTab(
        'all',
        [
          listFilter,
          { field: 'status', operator: 'isAny', value: ['unconfirmed'] },
        ],
        'status',
        statuses,
      ),
    ).to.equal('unconfirmed');
  });

  it('falls back to All when the status has no tab or several are picked', () => {
    expect(
      getActiveStatusTab(
        'all',
        [{ field: 'status', operator: 'isAny', value: ['bounced'] }],
        'status',
        statuses,
      ),
    ).to.equal('all');
    expect(
      getActiveStatusTab(
        'all',
        [
          {
            field: 'status',
            operator: 'isAny',
            value: ['subscribed', 'unconfirmed'],
          },
        ],
        'status',
        statuses,
      ),
    ).to.equal('all');
  });

  it('keeps Trash active whatever the status filter is', () => {
    expect(
      getActiveStatusTab(
        'trash',
        [{ field: 'status', operator: 'isAny', value: ['unconfirmed'] }],
        'status',
        statuses,
      ),
    ).to.equal('trash');
  });
});

describe('withStatusTab', () => {
  it('replaces any status filter with the tab status and keeps the others', () => {
    expect(
      withStatusTab(
        [
          listFilter,
          { field: 'status', operator: 'isNone', value: ['bounced'] },
        ],
        'status',
        'unconfirmed',
      ),
    ).to.deep.equal([
      listFilter,
      { field: 'status', operator: 'isAny', value: ['unconfirmed'] },
    ]);
  });

  it('removes only the status filter for the All tab', () => {
    expect(
      withStatusTab(
        [listFilter, { field: 'status', operator: 'isAny', value: ['sent'] }],
        'status',
        null,
      ),
    ).to.deep.equal([listFilter]);
  });
});
