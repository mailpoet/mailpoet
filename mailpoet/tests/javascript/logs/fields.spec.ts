import { formatCreatedAt } from '../../../assets/js/src/logs/fields';

describe('logs fields', () => {
  it('does not render Invalid date for null created_at values', () => {
    expect(formatCreatedAt(null)).to.equal('—');
    expect(formatCreatedAt(null)).not.to.equal('Invalid date');
  });
});
