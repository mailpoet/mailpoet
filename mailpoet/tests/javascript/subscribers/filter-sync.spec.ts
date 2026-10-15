import {
  pruneUnavailableFilters,
  pruneUnknownIds,
} from '../../../assets/js/src/subscribers/filter-sync';

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

describe('Subscribers pruneUnknownIds', () => {
  const known = { segments: ['4', 'without-list'], tags: ['7'] };

  it('returns null when nothing changed', () => {
    expect(pruneUnknownIds({ segment_in: [4], tag_in: [7] }, known)).to.equal(
      null,
    );
  });

  it('drops unknown ids value by value', () => {
    expect(
      pruneUnknownIds({ segment_in: [4, 9], segment_not_in: [9] }, known),
    ).to.deep.equal({ segment_in: [4] });
  });

  it('prunes tags in both directions', () => {
    expect(
      pruneUnknownIds({ tag_in: [7, 8], tag_not_in: [8, 7] }, known),
    ).to.deep.equal({ tag_in: [7], tag_not_in: [7] });
  });

  it('never prunes without-list or status values', () => {
    expect(
      pruneUnknownIds(
        { segment_in: ['without-list'], status_in: ['bounced'] },
        { segments: ['4'], tags: ['7'] },
      ),
    ).to.equal(null);
  });

  it('keeps other keys untouched', () => {
    expect(
      pruneUnknownIds({ segment_in: [9], score_gt: 2 }, known),
    ).to.deep.equal({ score_gt: 2 });
  });

  it('does not prune before options have loaded', () => {
    expect(
      pruneUnknownIds(
        { segment_in: [9], tag_in: [9] },
        { segments: [], tags: undefined },
      ),
    ).to.equal(null);
  });
});
