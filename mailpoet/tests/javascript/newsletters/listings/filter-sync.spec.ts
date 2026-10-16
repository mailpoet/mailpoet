import {
  pruneUnknownIds,
  shouldPruneAfterResponse,
} from '../../../../assets/js/src/newsletters/listings/filter-sync';

describe('newsletter listing filter sync', () => {
  describe('shouldPruneAfterResponse', () => {
    it('waits for a response after the prune was armed', () => {
      expect(shouldPruneAfterResponse(0, 0)).to.equal(false);
      expect(shouldPruneAfterResponse(1, 0)).to.equal(true);
      expect(shouldPruneAfterResponse(3, 3)).to.equal(false);
      expect(shouldPruneAfterResponse(4, 3)).to.equal(true);
    });

    it('never prunes once it is done', () => {
      expect(shouldPruneAfterResponse(99, Infinity)).to.equal(false);
    });
  });

  describe('pruneUnknownIds', () => {
    it('drops list ids that are not in the options', () => {
      expect(
        pruneUnknownIds(
          { segment_ids: ['1', '9'], segment_operator: 'isAny' },
          { segments: ['1', '2'] },
        ),
      ).to.deep.equal({ segment_ids: ['1'], segment_operator: 'isAny' });
    });

    it('removes the list filter when no id is left', () => {
      expect(
        pruneUnknownIds(
          {
            status: ['sent'],
            segment_ids: ['9'],
            segment_operator: 'isNone',
          },
          { segments: ['1'] },
        ),
      ).to.deep.equal({ status: ['sent'] });
    });

    it('returns null when every id is known', () => {
      expect(
        pruneUnknownIds(
          { segment_ids: ['1', '2'], segment_operator: 'isAny' },
          { segments: ['1', '2', '3'] },
        ),
      ).to.equal(null);
    });

    it('returns null without list ids', () => {
      expect(
        pruneUnknownIds({ status: ['sent'] }, { segments: ['1'] }),
      ).to.equal(null);
    });

    it('does not prune while the option list is missing', () => {
      const filter = { segment_ids: ['9'], segment_operator: 'isAny' as const };
      expect(pruneUnknownIds(filter, {})).to.equal(null);
    });

    it('removes every list id when the loaded option list is empty', () => {
      expect(
        pruneUnknownIds(
          {
            status: ['sent'],
            segment_ids: ['9'],
            segment_operator: 'isAny',
          },
          { segments: [] },
        ),
      ).to.deep.equal({ status: ['sent'] });
    });
  });
});
