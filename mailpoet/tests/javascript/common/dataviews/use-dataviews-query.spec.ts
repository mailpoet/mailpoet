import { getClampedPage } from '../../../../assets/js/src/common/dataviews/use-dataviews-query';

describe('useDataViewsQuery getClampedPage', () => {
  it('returns null when the requested page exists', () => {
    expect(getClampedPage(1, 3)).to.equal(null);
    expect(getClampedPage(3, 3)).to.equal(null);
    expect(getClampedPage(1, 0)).to.equal(null);
  });

  it('returns the last valid page when the request is beyond it', () => {
    expect(getClampedPage(5, 1)).to.equal(1);
    expect(getClampedPage(4, 3)).to.equal(3);
    expect(getClampedPage(2, 0)).to.equal(1);
  });
});
