import { getFormStyleValue } from '../../../../assets/js/src/form-editor/components/form-settings/form-style-value';

describe('Form style value', () => {
  it('clears input padding on reset', () => {
    expect(getFormStyleValue('inputPadding', undefined)).to.equal(undefined);
  });

  it('keeps a set input padding', () => {
    expect(getFormStyleValue('inputPadding', 0)).to.equal(0);
  });

  it('falls back to the default for other styles', () => {
    expect(getFormStyleValue('formPadding', undefined)).to.equal(20);
  });
});
