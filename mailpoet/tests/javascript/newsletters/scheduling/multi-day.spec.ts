import {
  DEFAULT_DAY,
  formatSelectedValues,
  getDefaultWeekDay,
  getOrderedWeekDayKeys,
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
  describe('getDefaultWeekDay', () => {
    it('uses the WordPress week start day when it is valid', () => {
      expect(getDefaultWeekDay('0')).to.equal('0');
      expect(getDefaultWeekDay(2)).to.equal('2');
    });

    it('falls back to Monday when the WordPress week start day is missing or invalid', () => {
      expect(getDefaultWeekDay(null)).to.equal(DEFAULT_DAY);
      expect(getDefaultWeekDay('8')).to.equal(DEFAULT_DAY);
    });
  });

  describe('getOrderedWeekDayKeys', () => {
    it('orders weekdays from the WordPress week start day', () => {
      expect(getOrderedWeekDayKeys('0')).to.deep.equal([
        '0',
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
      ]);
      expect(getOrderedWeekDayKeys('2')).to.deep.equal([
        '2',
        '3',
        '4',
        '5',
        '6',
        '0',
        '1',
      ]);
    });

    it('uses Monday-first order when the WordPress week start day is invalid', () => {
      expect(getOrderedWeekDayKeys('8')).to.deep.equal([
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '0',
      ]);
    });
  });

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

    it('renders labels in the provided value order', () => {
      expect(
        formatSelectedValues('0,5', weekDayValues, DEFAULT_DAY, [
          '1',
          '2',
          '3',
          '4',
          '5',
          '6',
          '0',
        ]),
      ).to.equal('Friday, Sunday');
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
