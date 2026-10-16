import type { Field } from '@wordpress/dataviews';
import type { NewsletterListingItem } from '../../../../assets/js/src/newsletters/api';
import {
  ACTIVATION_STATUS_ELEMENTS,
  applyFilterConfig,
  buildSegmentElements,
  getSegmentFilterIds,
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
  describe('getSegmentFilterIds', () => {
    it('returns the string ids of the segments filter', () => {
      expect(
        getSegmentFilterIds([
          { field: 'status', operator: 'isAny', value: ['draft'] },
          { field: 'segments', operator: 'isAny', value: [3, '4'] },
        ]),
      ).to.deep.equal(['3', '4']);
    });

    it('returns an empty list without a segments filter', () => {
      expect(getSegmentFilterIds(undefined)).to.deep.equal([]);
    });
  });

  describe('SENT_AT_OPERATORS', () => {
    it('only offers operators that map one-to-one onto the hash', () => {
      expect([...SENT_AT_OPERATORS]).to.deep.equal([
        'on',
        'beforeInc',
        'afterInc',
        'between',
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
        buildSegmentElements(
          [
            { label: 'All Lists', value: '' },
            { label: 'News', value: 2 },
          ],
          [],
        ),
      ).to.deep.equal([{ label: 'News', value: '2' }]);
    });

    it('adds selected ids that are not in the options', () => {
      expect(
        buildSegmentElements([{ label: 'News', value: '2' }], ['2', '9']),
      ).to.deep.equal([
        { label: 'News', value: '2' },
        { label: '9', value: '9' },
      ]);
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
        statusEnabled: true,
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
        statusEnabled: true,
      });
      expect(result.find((f) => f.id === 'segments')?.elements).to.equal(
        elements,
      );
    });

    it('disables the segments filter when there are no elements', () => {
      const result = applyFilterConfig(fields, {
        segmentElements: [],
        statusEnabled: true,
      });
      expect(result.find((f) => f.id === 'segments')?.filterBy).to.equal(false);
    });

    it('disables the status filter when it is not enabled', () => {
      const result = applyFilterConfig(fields, {
        segmentElements: elements,
        statusEnabled: false,
      });
      expect(result.find((f) => f.id === 'status')?.filterBy).to.equal(false);
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
