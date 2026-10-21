import {
  pruneUnknownIds,
  shouldPruneAfterResponse,
} from '../../../assets/js/src/subscribers/filter-sync';

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

describe('Subscribers shouldPruneAfterResponse', () => {
  it('waits for a response applied after the prune was armed', () => {
    expect(shouldPruneAfterResponse(0, 0)).to.equal(false);
    expect(shouldPruneAfterResponse(1, 0)).to.equal(true);
  });

  it('ignores a response that was already applied when re-armed', () => {
    expect(shouldPruneAfterResponse(4, 4)).to.equal(false);
    expect(shouldPruneAfterResponse(5, 4)).to.equal(true);
  });

  it('never prunes again once done', () => {
    expect(shouldPruneAfterResponse(9, Infinity)).to.equal(false);
  });
});
