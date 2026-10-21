import type { Field } from '@wordpress/dataviews';
import { resetLocaleData, setLocaleData } from '@wordpress/i18n';
import type { NewsletterListingItem } from '../../../../assets/js/src/newsletters/api';
import {
  ACTIVATION_STATUS_ELEMENTS,
  applyFilterConfig,
  buildSegmentElements,
  formatLabelWithCount,
  getStatusFilterValue,
  SENT_AT_OPERATORS,
  statusElements,
} from '../../../../assets/js/src/newsletters/listings/listing-fields';

type Row = NewsletterListingItem;

const field = (id: string, extra: Partial<Field<Row>> = {}): Field<Row> => ({
  id,
  label: id,
  ...extra,
});

describe('newsletters/listings/listing-fields', () => {
  describe('formatLabelWithCount', () => {
    afterEach(() => resetLocaleData());

    it('puts the count in brackets after the label and formats it for the locale', () => {
      expect(formatLabelWithCount('Sent', 1234)).to.equal(
        `Sent (${(1234).toLocaleString()})`,
      );
    });

    it('lets translators change the order and the brackets', () => {
      setLocaleData(
        { '%1$s (%2$s)': ['%2$s × %1$s'] } as unknown as Record<
          string,
          string[]
        >,
        'mailpoet',
      );
      expect(formatLabelWithCount('Sent', 5)).to.equal('5 × Sent');
    });
  });

  describe('SENT_AT_OPERATORS', () => {
    // DataViews starts a new filter on the first operator; between shows the
    // range presets, a single-day operator would show none.
    it('offers the round-tripping operators with between first', () => {
      expect([...SENT_AT_OPERATORS]).to.deep.equal([
        'between',
        'on',
        'before',
        'after',
      ]);
    });
  });

  describe('status elements', () => {
    it('builds the elements from the status values', () => {
      expect(statusElements(['sending', 'sent'])).to.deep.equal([
        { value: 'sending', label: 'Sending' },
        { value: 'sent', label: 'Sent' },
      ]);
    });

    it('labels the draft activation status as not active', () => {
      expect(ACTIVATION_STATUS_ELEMENTS).to.deep.equal([
        { value: 'active', label: 'Active' },
        { value: 'draft', label: 'Not active' },
      ]);
    });
  });

  describe('buildSegmentElements', () => {
    it('drops the "All Lists" entry and keeps the others', () => {
      expect(
        buildSegmentElements([
          { label: 'All Lists', value: '' },
          { label: 'News', value: 2 },
        ]),
      ).to.deep.equal([{ label: 'News', value: '2' }]);
    });

    it('returns no elements without options', () => {
      expect(buildSegmentElements(undefined)).to.deep.equal([]);
    });
  });

  describe('applyFilterConfig', () => {
    const elements = [{ label: 'News', value: '2' }];
    const fields = [
      field('name'),
      field('status', { filterBy: { operators: ['isAny'] } }),
      field('segments', { filterBy: { operators: ['isAny', 'isNone'] } }),
      field('sent_at', { filterBy: { operators: ['before'] } }),
      field('statistics'),
    ];

    it('disables filtering on every field that is not a filter', () => {
      const result = applyFilterConfig(fields, {
        segmentElements: elements,
        statusLabels: {},
      });
      expect(result.find((f) => f.id === 'name')?.filterBy).to.equal(false);
      expect(result.find((f) => f.id === 'statistics')?.filterBy).to.equal(
        false,
      );
      expect(result.find((f) => f.id === 'status')?.filterBy).to.deep.equal({
        operators: ['isAny'],
      });
      expect(result.find((f) => f.id === 'sent_at')?.filterBy).to.deep.equal({
        operators: ['before'],
      });
    });

    it('sets the segment elements', () => {
      const result = applyFilterConfig(fields, {
        segmentElements: elements,
        statusLabels: {},
      });
      expect(result.find((f) => f.id === 'segments')?.elements).to.equal(
        elements,
      );
    });

    it('disables the segments filter when there are no elements', () => {
      const result = applyFilterConfig(fields, {
        segmentElements: [],
        statusLabels: {},
      });
      expect(result.find((f) => f.id === 'segments')?.filterBy).to.equal(false);
    });

    it('keeps the status filter enabled', () => {
      const result = applyFilterConfig(fields, {
        segmentElements: elements,
        statusLabels: {},
      });
      expect(result.find((f) => f.id === 'status')?.filterBy).to.deep.equal({
        operators: ['isAny'],
      });
    });

    it('labels the status options from the response labels', () => {
      const withElements = [
        field('status', {
          filterBy: { operators: ['isAny'] },
          elements: [
            { value: 'draft', label: 'Draft' },
            { value: 'sent', label: 'Sent' },
          ],
        }),
      ];
      const result = applyFilterConfig(withElements, {
        segmentElements: elements,
        statusLabels: { draft: 'Draft (2)' },
      });
      expect(result[0].elements).to.deep.equal([
        { value: 'draft', label: 'Draft (2)' },
        { value: 'sent', label: 'Sent' },
      ]);
    });
  });

  describe('getStatusFilterValue', () => {
    it('reports a paused sending email as paused', () => {
      expect(
        getStatusFilterValue({
          status: 'sending',
          queue: { status: 'paused' },
        } as never),
      ).to.equal('paused');
    });

    it('returns the status otherwise', () => {
      expect(
        getStatusFilterValue({
          status: 'sending',
          queue: { status: null },
        } as never),
      ).to.equal('sending');
      expect(
        getStatusFilterValue({ status: 'sent', queue: false } as never),
      ).to.equal('sent');
    });
  });
});
