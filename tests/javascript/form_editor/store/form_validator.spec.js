import { expect } from 'chai';
import validate from '../../../../assets/js/src/form_editor/store/form_validator.jsx';

const emailBlock = {
  clientId: 'email',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/email-input',
  attributes: {
    label: 'Email Address',
    labelWithinInput: false,
  },
};
const submitBlock = {
  clientId: 'submit',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/submit-button',
  attributes: {
    label: 'Subscribe!',
  },
};

describe('Form validator', () => {
  it('Should return no errors for valid data', () => {
    const formData = {
      settings: {
        segments: [1],
      },
    };
    const blocks = [emailBlock, submitBlock];
    const result = validate(formData, blocks);
    expect(result).to.be.empty;
  });

  it('Should return error for missing lists', () => {
    const formData = {
      settings: {
        segments: [],
      },
    };
    const blocks = [emailBlock, submitBlock];
    const result = validate(formData, blocks);
    expect(result).to.contain('missing-lists');
  });

  it('Should return error for missing email', () => {
    const formData = {
      settings: {
        segments: [1],
      },
    };
    const blocks = [submitBlock];
    const result = validate(formData, blocks);
    expect(result).to.contain('missing-email-input');
  });

  it('Should return error for missing submit', () => {
    const formData = {
      settings: {
        segments: [1],
      },
    };
    const blocks = [emailBlock];
    const result = validate(formData, blocks);
    expect(result).to.contain('missing-submit');
  });

  it('Should return multiple errors', () => {
    const formData = {
      settings: {
        segments: [],
      },
    };
    const blocks = [];
    const result = validate(formData, blocks);
    expect(result).to.contain('missing-submit');
    expect(result).to.contain('missing-email-input');
    expect(result).to.contain('missing-lists');
  });

  it('Should throw errors for invalid inputs', () => {
    const formData = {
      settings: {
        segments: [1],
      },
    };
    const blocks = [emailBlock, submitBlock];
    const formDataError = 'formData.settings.segments are expected to be an array.';
    expect(() => validate(null, blocks)).to.throw(formDataError);
    expect(() => validate({ settings: {} }, blocks)).to.throw(formDataError);
    const blocksError = 'formBlocks are expected to be an array.';
    expect(() => validate(formData, null)).to.throw(blocksError);
    expect(() => validate(formData, 'string')).to.throw(blocksError);
  });
});
