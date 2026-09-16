import { calculateDelta } from '../../../../../../../assets/js/src/automation/integrations/mailpoet/analytics/formatter/calculate-delta';

describe('automation analytics delta formatter', () => {
  it('returns 0 when the previous period is 0', () => {
    expect(calculateDelta(50, 0)).to.equal(0);
  });

  it('returns 0 when nothing changed', () => {
    expect(calculateDelta(80, 80)).to.equal(0);
  });

  it('calculates a rise', () => {
    expect(calculateDelta(120, 100)).to.equal(20);
  });

  it('calculates a drop', () => {
    expect(calculateDelta(75, 100)).to.equal(-25);
  });
});
