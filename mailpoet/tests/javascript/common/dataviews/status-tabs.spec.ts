import { expect } from 'chai';
import type { Filter } from '@wordpress/dataviews';

import {
  getActiveStatusTab,
  getSingleFilterValue,
  getTabChange,
  groupsToTabs,
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

describe('getTabChange', () => {
  const status = (value: string[], operator: 'isAny' | 'isNone' = 'isAny') =>
    ({ field: 'status', operator, value } as Filter);

  it('sets the status filter for a status tab and keeps the others', () => {
    expect(
      getTabChange('unconfirmed', 'all', [listFilter], 'status'),
    ).to.deep.equal({
      group: 'all',
      filters: [listFilter, status(['unconfirmed'])],
    });
  });

  it('clears a status filter that has no tab when All is picked', () => {
    expect(
      getTabChange('all', 'all', [status(['bounced'], 'isNone')], 'status'),
    ).to.deep.equal({ group: 'all', filters: [] });
    expect(
      getTabChange('all', 'all', [status(['a', 'b'])], 'status'),
    ).to.deep.equal({ group: 'all', filters: [] });
  });

  it('clears only the status filter when Trash is picked', () => {
    expect(
      getTabChange('trash', 'all', [status(['sent']), listFilter], 'status'),
    ).to.deep.equal({ group: 'trash', filters: [listFilter] });
  });

  it('keeps a status filter set inside Trash when Trash is picked again', () => {
    expect(
      getTabChange('trash', 'trash', [status(['sent'])], 'status'),
    ).to.equal(null);
  });

  it('returns null when the tab changes nothing', () => {
    expect(getTabChange('all', 'all', [listFilter], 'status')).to.equal(null);
    expect(getTabChange('sent', 'all', [status(['sent'])], 'status')).to.equal(
      null,
    );
    expect(getTabChange('trash', 'trash', [], 'status')).to.equal(null);
    expect(
      getTabChange('sent', 'all', [status(['sent']), listFilter], 'status'),
    ).to.equal(null);
  });
});

describe('groupsToTabs', () => {
  const groups = [
    { name: 'all', label: 'All', count: 3 },
    { name: 'sent', label: 'Sent', count: 3 },
    { name: 'trash', label: 'Trash', count: 0 },
  ];

  it('hides an empty Trash tab unless Trash is open', () => {
    expect(groupsToTabs(groups, 'all').map((tab) => tab.name)).to.deep.equal([
      'all',
      'sent',
    ]);
    expect(groupsToTabs(groups, 'trash').map((tab) => tab.name)).to.deep.equal([
      'all',
      'sent',
      'trash',
    ]);
  });

  it('keeps the tab classes and ids the listings use', () => {
    expect(groupsToTabs(groups, 'all')[1]).to.deep.equal({
      name: 'sent',
      label: 'Sent',
      count: 3,
      automationId: 'filters_sent',
      className: 'mailpoet-dataviews-group-sent',
    });
  });
});
