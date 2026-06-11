import {
  SELECT_ALL_WARNING_THRESHOLD,
  selectAllBannerState,
  shouldWarnLargeOperation,
} from '../../../assets/js/src/subscribers/select-all';

describe('Subscribers select-all helpers', () => {
  describe('selectAllBannerState', () => {
    const base = {
      pageItemCount: 25,
      totalCount: 47312,
      totalPages: 1893,
      isPageFullySelected: false,
      isSelectAll: false,
    };

    it('is hidden when there is one page or fewer', () => {
      expect(
        selectAllBannerState({
          ...base,
          totalPages: 1,
          isPageFullySelected: true,
        }),
      ).to.equal('hidden');
    });

    it('is hidden when nothing matches', () => {
      expect(
        selectAllBannerState({
          ...base,
          totalCount: 0,
          isPageFullySelected: true,
        }),
      ).to.equal('hidden');
    });

    it('offers select-all when the current page is fully selected', () => {
      expect(
        selectAllBannerState({ ...base, isPageFullySelected: true }),
      ).to.equal('offer');
    });

    it('is hidden when the page is not fully selected and select-all is off', () => {
      expect(selectAllBannerState(base)).to.equal('hidden');
    });

    it('is active whenever select-all is on, regardless of page selection', () => {
      expect(
        selectAllBannerState({
          ...base,
          isPageFullySelected: false,
          isSelectAll: true,
        }),
      ).to.equal('active');
    });
  });

  describe('shouldWarnLargeOperation', () => {
    it('warns above the threshold', () => {
      expect(
        shouldWarnLargeOperation(SELECT_ALL_WARNING_THRESHOLD + 1),
      ).to.equal(true);
    });

    it('does not warn at or below the threshold', () => {
      expect(shouldWarnLargeOperation(SELECT_ALL_WARNING_THRESHOLD)).to.equal(
        false,
      );
      expect(shouldWarnLargeOperation(10)).to.equal(false);
    });
  });
});
