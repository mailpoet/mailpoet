import {
  calculateCoverageDelta,
  calculateDelta,
} from '../../../../../../../assets/js/src/automation/integrations/mailpoet/analytics/formatter/calculate-delta';

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

describe('automation analytics tracking coverage delta', () => {
  it('has no delta when the previous period sent nothing', () => {
    expect(
      calculateCoverageDelta(
        { current: 40, previous: 100 },
        { current: 1000, previous: 0 },
      ),
    ).to.equal(undefined);
  });

  it('has no delta without coverage data', () => {
    expect(
      calculateCoverageDelta(undefined, { current: 10, previous: 10 }),
    ).to.equal(undefined);
  });

  it('compares coverage when both periods sent something', () => {
    expect(
      calculateCoverageDelta(
        { current: 40, previous: 80 },
        { current: 1000, previous: 500 },
      ),
    ).to.equal(-50);
  });
});
