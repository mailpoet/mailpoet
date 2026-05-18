import {
  formatCreatedAt,
  getLogFields,
} from '../../../assets/js/src/logs/fields';

describe('logs fields', () => {
  it('does not render Invalid date for null created_at values', () => {
    expect(formatCreatedAt(null)).to.equal('—');
    expect(formatCreatedAt(null)).not.to.equal('Invalid date');
  });

  it('does not expose unsupported DataViews column filters', () => {
    const fields = getLogFields(new Set(), () => {});

    expect(fields.map((field) => field.filterBy)).to.deep.equal([
      false,
      false,
      false,
      false,
    ]);
  });
});
