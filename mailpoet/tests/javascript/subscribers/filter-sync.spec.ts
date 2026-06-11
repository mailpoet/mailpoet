import { pruneUnavailableFilters } from '../../../assets/js/src/subscribers/filter-sync';

describe('Subscribers filter sync', () => {
  const filters = {
    segment: [{ value: '' }, { value: 4 }, { value: 'mailpoet_without_list' }],
    tag: [{ value: '' }, { value: 7 }],
  };

  it('returns null when every active filter is still selectable', () => {
    expect(pruneUnavailableFilters({ segment: '4' }, filters)).to.equal(null);
  });

  it('removes a filter whose value is no longer an option', () => {
    expect(pruneUnavailableFilters({ segment: '9' }, filters)).to.deep.equal(
      {},
    );
  });

  it('keeps still-valid filters while removing invalid ones', () => {
    expect(
      pruneUnavailableFilters({ segment: '9', tag: '7' }, filters),
    ).to.deep.equal({ tag: '7' });
  });

  it('does not prune before options have loaded', () => {
    expect(pruneUnavailableFilters({ segment: '4' }, null)).to.equal(null);
    expect(pruneUnavailableFilters({ segment: '4' }, { segment: [] })).to.equal(
      null,
    );
  });

  it('ignores empty filter values', () => {
    expect(pruneUnavailableFilters({ segment: '' }, filters)).to.equal(null);
  });
});
