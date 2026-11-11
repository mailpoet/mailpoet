import { mapCustomFieldBeforeSaving } from '../../../../assets/js/src/form-editor/store/map-custom-field-before-saving';
import { createCustomFieldMock } from './mocks/partial-mocks';

const storedField = createCustomFieldMock({
  id: 5,
  name: 'Colour',
  type: 'radio',
  params: {
    required: '',
    label: 'Colour',
    values: [
      { value: 'Blue', is_checked: '' },
      { value: 'Green', is_checked: '' },
      { value: 'Red', is_checked: '' },
    ],
  },
});

describe('Custom Field Save Mapper', () => {
  it('Keeps only the values that were sent', () => {
    const result = mapCustomFieldBeforeSaving(storedField, {
      params: {
        required: '',
        label: 'Colour',
        values: [
          { value: 'GreenX', is_checked: '' },
          { value: 'Red', is_checked: '1' },
        ],
      },
    });
    expect(result.params.values).to.deep.equal([
      { value: 'GreenX', is_checked: '' },
      { value: 'Red', is_checked: '1' },
    ]);
  });

  it('Does not keep stored params that were not sent', () => {
    const result = mapCustomFieldBeforeSaving(
      { ...storedField, params: { ...storedField.params, legacy_key: 'x' } },
      { params: { required: '1', label: 'Colour', values: [] } },
    );
    expect(result.params).to.deep.equal({
      required: '1',
      label: 'Colour',
      values: [],
    });
  });

  it('Keeps the stored id, name and type', () => {
    const result = mapCustomFieldBeforeSaving(storedField, {
      params: { required: '', label: 'Renamed', values: [] },
    });
    expect(result.id).to.equal(5);
    expect(result.name).to.equal('Colour');
    expect(result.type).to.equal('radio');
  });
});
