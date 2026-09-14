import { getTrackedSent } from '../../../assets/js/src/newsletters/tracked-sent';

describe('getTrackedSent', () => {
  it('takes the untracked recipients out of the total shown', () => {
    expect(getTrackedSent(500, 20)).to.equal(480);
  });

  it('keeps the whole total while nothing is counted as untracked yet', () => {
    expect(getTrackedSent(500, 0)).to.equal(500);
  });

  it('never goes below zero when the counts drift', () => {
    expect(getTrackedSent(3, 5)).to.equal(0);
  });
});
