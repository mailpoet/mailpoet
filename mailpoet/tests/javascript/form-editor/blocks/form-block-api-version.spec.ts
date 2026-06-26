import {
  FORM_BLOCK_API_VERSION,
  withFormBlockApiVersion,
} from '../../../../assets/js/src/form-editor/blocks/form-block-api-version';

describe('form block API version', () => {
  it('marks form block settings as API version 3', () => {
    const settings = withFormBlockApiVersion({
      title: 'Email',
      apiVersion: 1,
    });

    expect(FORM_BLOCK_API_VERSION).to.equal(3);
    expect(settings.apiVersion).to.equal(3);
  });

  it('preserves the original block settings', () => {
    const edit = () => null;
    const settings = withFormBlockApiVersion({
      title: 'Email',
      edit,
      supports: { html: false },
    });

    expect(settings.title).to.equal('Email');
    expect(settings.edit).to.equal(edit);
    expect(settings.supports).to.deep.equal({ html: false });
  });
});
