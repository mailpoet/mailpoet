import { expect } from 'chai';
import { isEmpty } from 'lodash';
import { validateForm as validate } from '../../../../assets/js/src/form_editor/store/form_validator.jsx';

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

const segmentsBlock = {
  clientId: 'segments',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/segment-select',
  attributes: {
    values: [],
  },
};

const columns = {
  clientId: 'columns-1',
  name: 'core/columns',
  isValid: true,
  attributes: {
    verticalAlignment: 'center',
  },
  innerBlocks: [
    {
      clientId: 'column-1-1',
      name: 'core/column',
      isValid: true,
      attributes: {
        width: 66.66,
        verticalAlignment: 'center',
      },
      innerBlocks: [emailBlock, submitBlock],
    },
  ],
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
    expect(isEmpty(result)).to.be.equal(true);
  });

  it('Should validate form data with nested email and submit', () => {
    const formData = {
      settings: {
        segments: [1],
      },
    };
    const blocks = [columns];
    const result = validate(formData, blocks);
    expect(isEmpty(result)).to.be.equal(true);
  });

  it('Should return error for missing lists', () => {
    const formData = {
      settings: {
        segments: [],
      },
    };
    const blocks = [emailBlock, submitBlock, segmentsBlock];
    const result = validate(formData, blocks);
    expect(result).to.contain('missing-lists');
  });

  it('Should not return error for when segments block with lists is present', () => {
    const filledSegmentsBlock = { ...segmentsBlock };
    filledSegmentsBlock.attributes.values = [{ id: 1, name: 'cool people' }];
    const formData = {
      settings: {
        segments: [],
      },
    };
    const blocks = [emailBlock, submitBlock, filledSegmentsBlock];
    const result = validate(formData, blocks);
    expect(isEmpty(result)).to.be.equal(true);
  });

  it('Should return error for when segments block is empty', () => {
    const filledSegmentsBlock = { ...segmentsBlock };
    filledSegmentsBlock.attributes.values = [];
    const formData = {
      settings: {
        segments: [1],
      },
    };
    const blocks = [emailBlock, submitBlock, filledSegmentsBlock];
    const result = validate(formData, blocks);
    expect(isEmpty(result)).to.be.equal(false);
    expect(result).to.contain('missing-lists-in-custom-segments-block');
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
    const formDataError =
      'formData.settings.segments are expected to be an array.';
    expect(() => validate(null, blocks)).to.throw(formDataError);
    expect(() => validate({ settings: {} }, blocks)).to.throw(formDataError);
    const blocksError = 'formBlocks are expected to be an array.';
    expect(() => validate(formData, null)).to.throw(blocksError);
    expect(() => validate(formData, 'string')).to.throw(blocksError);
  });
});
