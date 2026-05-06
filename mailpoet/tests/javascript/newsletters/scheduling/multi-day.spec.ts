import {
  DEFAULT_DAY,
  formatSelectedValues,
  parseSelectedValues,
  serializeSelectedValues,
} from '../../../../assets/js/src/newsletters/scheduling/multi-day';

const weekDayValues = {
  '0': 'Sunday',
  '1': 'Monday',
  '2': 'Tuesday',
  '3': 'Wednesday',
  '4': 'Thursday',
  '5': 'Friday',
  '6': 'Saturday',
};

const monthDayValues = {
  '1': '1st',
  '2': '2nd',
  '3': '3rd',
  '15': '15th',
  '28': '28th',
};

describe('multi-day helpers', () => {
  describe('parseSelectedValues', () => {
    it('returns the default when value is null', () => {
      expect(
        parseSelectedValues(null, DEFAULT_DAY, weekDayValues),
      ).to.deep.equal(['1']);
    });

    it('returns the default when value is undefined', () => {
      expect(
        parseSelectedValues(undefined, DEFAULT_DAY, weekDayValues),
      ).to.deep.equal(['1']);
    });

    it('returns the default when value is an empty string', () => {
      expect(parseSelectedValues('', DEFAULT_DAY, weekDayValues)).to.deep.equal(
        ['1'],
      );
    });

    it('preserves Sunday (0) without coercing to default', () => {
      expect(
        parseSelectedValues('0', DEFAULT_DAY, weekDayValues),
      ).to.deep.equal(['0']);
    });

    it('preserves a numeric 0 input without coercing to default', () => {
      expect(parseSelectedValues(0, DEFAULT_DAY, weekDayValues)).to.deep.equal([
        '0',
      ]);
    });

    it('parses a comma-separated list, sorts numerically, and de-duplicates', () => {
      expect(
        parseSelectedValues('4,2,2', DEFAULT_DAY, weekDayValues),
      ).to.deep.equal(['2', '4']);
    });

    it('trims whitespace around list entries', () => {
      expect(
        parseSelectedValues(' 1 , 3 ', DEFAULT_DAY, weekDayValues),
      ).to.deep.equal(['1', '3']);
    });

    it('drops entries that are not in availableValues', () => {
      expect(
        parseSelectedValues('8,1,invalid', DEFAULT_DAY, weekDayValues),
      ).to.deep.equal(['1']);
    });

    it('falls back to the default when no entry is valid', () => {
      expect(
        parseSelectedValues('8,invalid', DEFAULT_DAY, weekDayValues),
      ).to.deep.equal(['1']);
    });

    it('sorts month days numerically (15 after 2, not lexicographically)', () => {
      expect(
        parseSelectedValues('15,2', DEFAULT_DAY, monthDayValues),
      ).to.deep.equal(['2', '15']);
    });
  });

  describe('serializeSelectedValues', () => {
    it('joins entries with a comma', () => {
      expect(serializeSelectedValues(['1', '2'])).to.equal('1,2');
    });

    it('sorts numerically before joining', () => {
      expect(serializeSelectedValues(['15', '2', '1'])).to.equal('1,2,15');
    });

    it('returns an empty string for an empty array', () => {
      expect(serializeSelectedValues([])).to.equal('');
    });
  });

  describe('formatSelectedValues', () => {
    it('renders labels joined by ", " in numeric order', () => {
      expect(formatSelectedValues('4,2', weekDayValues, DEFAULT_DAY)).to.equal(
        'Tuesday, Thursday',
      );
    });

    it('renders the default label when the value is missing', () => {
      expect(formatSelectedValues(null, weekDayValues, DEFAULT_DAY)).to.equal(
        'Monday',
      );
    });

    it('renders Sunday when the value is 0', () => {
      expect(formatSelectedValues('0', weekDayValues, DEFAULT_DAY)).to.equal(
        'Sunday',
      );
    });

    it('drops invalid entries before rendering', () => {
      expect(formatSelectedValues('8,1', weekDayValues, DEFAULT_DAY)).to.equal(
        'Monday',
      );
    });

    it('falls back to the default label when no entry is valid', () => {
      expect(
        formatSelectedValues('8,invalid', weekDayValues, DEFAULT_DAY),
      ).to.equal('Monday');
    });
  });
});
