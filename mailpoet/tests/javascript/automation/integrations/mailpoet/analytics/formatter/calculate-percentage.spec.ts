import { calculatePercentage } from '../../../../../../../assets/js/src/automation/integrations/mailpoet/analytics/formatter/calculate-percentage';

describe('automation analytics percentage formatter', () => {
  it('returns 0 when the base is 0', () => {
    expect(calculatePercentage(25, 0)).to.equal(0);
  });

  it('calculates a percentage from the provided value and base', () => {
    expect(calculatePercentage(25, 200)).to.equal(12.5);
  });

  it('calculates positive comparison deltas', () => {
    expect(calculatePercentage(125, 100, true)).to.equal(25);
  });

  it('calculates negative comparison deltas', () => {
    expect(calculatePercentage(75, 100, true)).to.equal(-25);
  });
});
