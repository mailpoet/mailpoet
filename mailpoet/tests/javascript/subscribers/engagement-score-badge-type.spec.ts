import { getEngagementScoreBadgeType } from '../../../assets/js/src/subscribers/engagement-score-badge-type';

describe('getEngagementScoreBadgeType', () => {
  it('maps the backend low engagement type to the existing badge variant', () => {
    expect(getEngagementScoreBadgeType(12, 'low')).to.equal('average');
  });

  it('uses backend engagement types before score thresholds', () => {
    expect(getEngagementScoreBadgeType(80, 'dormant')).to.equal('dormant');
  });

  it('falls back to score thresholds when no type is provided', () => {
    expect(getEngagementScoreBadgeType(12)).to.equal('average');
    expect(getEngagementScoreBadgeType(35)).to.equal('good');
    expect(getEngagementScoreBadgeType(70)).to.equal('excellent');
    expect(getEngagementScoreBadgeType(null)).to.equal('unknown');
  });
});
