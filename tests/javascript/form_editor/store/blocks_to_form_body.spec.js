import { expect } from 'chai';
import formBlocksToBody from '../../../../assets/js/src/form_editor/store/blocks_to_form_body.jsx';

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
    labelWithinInput: false,
    mandatory: false,
    label: 'Select list(s):',
    values: [
      { id: '6', name: 'Unicorn Truthers' },
      { id: '24', name: 'Carrots are lit', isChecked: true },
      { id: '29', name: 'Daily' },
    ],
  },
};

const firstNameBlock = {
  clientId: 'first_name',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/first-name-input',
  attributes: {
    label: 'First Name',
    labelWithinInput: false,
    mandatory: false,
  },
};

const lastNameBlock = {
  clientId: 'last_name',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/last-name-input',
  attributes: {
    label: 'Last Name',
    labelWithinInput: false,
    mandatory: false,
  },
};

const customTextBlock = {
  clientId: '2',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/custom-text',
  attributes: {
    label: 'Name of the street',
    labelWithinInput: false,
    mandatory: false,
    validate: 'alphanum',
    customFieldId: 1,
  },
};

const customRadioBlock = {
  clientId: '4',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/custom-radio',
  attributes: {
    label: 'Options',
    hideLabel: true,
    mandatory: true,
    customFieldId: 2,
    values: [
      { name: 'option 1' },
      { name: 'option 2' },
    ],
  },
};

const customCheckBox = {
  clientId: '5',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/custom-checkbox',
  attributes: {
    label: 'Checkbox',
    hideLabel: false,
    mandatory: false,
    customFieldId: 3,
    values: [
      {
        name: 'Check this',
        isChecked: true,
      },
    ],
  },
};

const customSelectBlock = {
  clientId: '5',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/custom-select',
  attributes: {
    label: 'Select',
    labelWithinInput: false,
    mandatory: false,
    customFieldId: 6,
    values: [
      { name: 'option 1' },
      { name: 'option 2' },
    ],
  },
};

const dividerBlock = {
  clientId: 'some_random_123',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/divider',
  attributes: {},
};

const customHtmlBlock = {
  clientId: 'some_random_321',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/html',
  attributes: {
    content: 'HTML content',
    nl2br: true,
  },
};

const checkBodyInputBasics = (input) => {
  expect(input.id).to.be.a('string');
  expect(parseInt(input.position, 10)).to.be.a('number');
  expect(input.type).to.be.a('string');
  expect(input.type).to.be.not.empty;
};

describe('Blocks to Form Body', () => {
  it('Should throw an error for wrong input', () => {
    const error = 'Mapper expects blocks to be an array.';
    expect(() => formBlocksToBody(null)).to.throw(error);
    expect(() => formBlocksToBody('hello')).to.throw(error);
    expect(() => formBlocksToBody(undefined)).to.throw(error);
    expect(() => formBlocksToBody(1)).to.throw(error);
  });

  it('Should map email block to input data', () => {
    const [input] = formBlocksToBody([emailBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('email');
    expect(input.name).to.be.equal('Email');
    expect(input.type).to.be.equal('text');
    expect(input.position).to.be.equal('1');
    expect(input.unique).to.be.equal('0');
    expect(input.static).to.be.equal('1');
    expect(input.params.label).to.be.equal('Email Address');
    expect(input.params.required).to.be.equal('1');
    expect(input.params.label_within).to.be.undefined;
  });

  it('Should map email block with label within', () => {
    const block = { ...emailBlock };
    block.attributes.labelWithinInput = true;
    const [input] = formBlocksToBody([block]);
    checkBodyInputBasics(input);
    expect(input.params.label_within).to.be.equal('1');
  });

  it('Should map last name block to input data', () => {
    const [input] = formBlocksToBody([lastNameBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('last_name');
    expect(input.name).to.be.equal('Last name');
    expect(input.type).to.be.equal('text');
    expect(input.position).to.be.equal('1');
    expect(input.unique).to.be.equal('1');
    expect(input.static).to.be.equal('0');
    expect(input.params.label).to.be.equal('Last Name');
    expect(input.params.required).to.be.undefined;
    expect(input.params.label_within).to.be.undefined;
  });

  it('Should map last name block with mandatory and label', () => {
    const block = { ...lastNameBlock };
    block.attributes.labelWithinInput = true;
    block.attributes.mandatory = true;
    const [input] = formBlocksToBody([block]);
    checkBodyInputBasics(input);
    expect(input.params.required).to.be.equal('1');
    expect(input.params.label_within).to.be.equal('1');
  });

  it('Should map first name block to input data', () => {
    const [input] = formBlocksToBody([firstNameBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('first_name');
    expect(input.name).to.be.equal('First name');
    expect(input.type).to.be.equal('text');
    expect(input.position).to.be.equal('1');
    expect(input.unique).to.be.equal('1');
    expect(input.static).to.be.equal('0');
    expect(input.params.label).to.be.equal('First Name');
    expect(input.params.required).to.be.undefined;
    expect(input.params.label_within).to.be.undefined;
  });

  it('Should map first name block with mandatory and label', () => {
    const block = { ...firstNameBlock };
    block.attributes.labelWithinInput = true;
    block.attributes.mandatory = true;
    const [input] = formBlocksToBody([block]);
    checkBodyInputBasics(input);
    expect(input.params.required).to.be.equal('1');
    expect(input.params.label_within).to.be.equal('1');
  });

  it('Should map segments', () => {
    const [input] = formBlocksToBody([segmentsBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('segments');
    expect(input.name).to.be.equal('List selection');
    expect(input.type).to.be.equal('segment');
    expect(input.params.values).to.be.an('Array');
    expect(input.params.values[0]).to.have.property('name', 'Unicorn Truthers');
    expect(input.params.values[0]).to.have.property('id', '6');
    expect(input.params.values[1]).to.have.property('is_checked', '1');
  });

  it('Should map submit block to input data', () => {
    const [input] = formBlocksToBody([submitBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('submit');
    expect(input.name).to.be.equal('Submit');
    expect(input.type).to.be.equal('submit');
    expect(input.position).to.be.equal('1');
    expect(input.unique).to.be.equal('0');
    expect(input.static).to.be.equal('1');
    expect(input.params.label).to.be.equal('Subscribe!');
  });

  it('Should map divider block to input data', () => {
    const [divider] = formBlocksToBody([dividerBlock]);
    checkBodyInputBasics(divider);
    expect(divider.id).to.be.equal('divider');
    expect(divider.name).to.be.equal('Divider');
    expect(divider.type).to.be.equal('divider');
    expect(divider.position).to.be.equal('1');
    expect(divider.unique).to.be.equal('0');
    expect(divider.static).to.be.equal('0');
    expect(divider.params).to.be.equal('');
  });

  it('Should map multiple dividers', () => {
    const [divider1, divider2] = formBlocksToBody([dividerBlock, dividerBlock]);
    checkBodyInputBasics(divider1);
    checkBodyInputBasics(divider2);
    expect(divider1.id).to.be.equal('divider');
    expect(divider2.id).to.be.equal('divider');
    expect(divider1.position).to.be.equal('1');
    expect(divider2.position).to.be.equal('2');
  });

  it('Should custom html block to form data', () => {
    const [html] = formBlocksToBody([customHtmlBlock]);
    checkBodyInputBasics(html);
    expect(html.id).to.be.equal('html');
    expect(html.name).to.be.equal('Custom text or HTML');
    expect(html.type).to.be.equal('html');
    expect(html.position).to.be.equal('1');
    expect(html.unique).to.be.equal('0');
    expect(html.static).to.be.equal('0');
    expect(html.params.text).to.be.equal('HTML content');
    expect(html.params.nl2br).to.be.equal('1');
  });

  it('Should map custom text field', () => {
    const customField = {
      created_at: '2019-12-10T15:05:06+00:00',
      id: 1,
      name: 'Custom Field name',
      params: {
        label: 'Street name',
        required: '1',
        validate: '',
      },
      type: 'text',
      updated_at: '2019-12-10T15:05:06+00:00',
    };
    const [input] = formBlocksToBody([customTextBlock], [customField]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('1');
    expect(input.name).to.be.equal('Custom Field name');
    expect(input.type).to.be.equal('text');
    expect(input.position).to.be.equal('1');
    expect(input.params.label).to.be.equal('Name of the street');
    expect(input.params.required).to.be.undefined;
    expect(input.params.label_within).to.be.undefined;
    expect(input.params.validate).to.eq('alphanum');
  });

  it('Should map custom select field', () => {
    const customField = {
      created_at: '2019-12-10T15:05:06+00:00',
      id: 6,
      name: 'Custom Select',
      params: {
        label: 'Select',
        required: '1',
        values: [
          { value: 'option 1' },
        ],
      },
      type: 'select',
      updated_at: '2019-12-10T15:05:06+00:00',
    };
    const [input] = formBlocksToBody([customSelectBlock], [customField]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('6');
    expect(input.name).to.be.equal('Custom Select');
    expect(input.type).to.be.equal('select');
    expect(input.position).to.be.equal('1');
    expect(input.params.label).to.be.equal('Select');
    expect(input.params.values).to.be.an('Array').that.has.length(2);
    expect(input.params.values[0]).to.have.property('value', 'option 1');
    expect(input.params.values[1]).to.have.property('value', 'option 2');
  });

  it('Should map custom radio field', () => {
    const customField = {
      created_at: '2019-12-10T15:05:06+00:00',
      id: 2,
      name: 'Custom Field name',
      params: {
        label: 'Options',
        required: '1',
        values: [
          { value: 'option 1' },
        ],
      },
      type: 'radio',
      updated_at: '2019-12-10T15:05:06+00:00',
    };
    const [input] = formBlocksToBody([customRadioBlock], [customField]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('2');
    expect(input.name).to.be.equal('Custom Field name');
    expect(input.type).to.be.equal('radio');
    expect(input.position).to.be.equal('1');
    expect(input.params.label).to.be.equal('Options');
    expect(input.params.required).to.be.eq('1');
    expect(input.params.hide_label).to.eq('1');
    expect(input.params.values).to.be.an('Array').that.has.length(2);
    expect(input.params.values[0]).to.have.property('value', 'option 1');
    expect(input.params.values[1]).to.have.property('value', 'option 2');
  });

  it('Should map custom checkbox field', () => {
    const customField = {
      created_at: '2019-12-13T15:22:07+00:00',
      id: 3,
      name: 'Custom Checkbox',
      params: {
        label: 'Check',
        required: '1',
        values: [
          { value: 'option 1' },
        ],
      },
      type: 'checkbox',
      updated_at: '2019-12-13T15:22:07+00:00',
    };
    const [input] = formBlocksToBody([customCheckBox], [customField]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('3');
    expect(input.name).to.be.equal('Custom Checkbox');
    expect(input.type).to.be.equal('checkbox');
    expect(input.position).to.be.equal('1');
    expect(input.params.label).to.be.equal('Checkbox');
    expect(input.params.required).to.be.be.undefined;
    expect(input.params.hide_label).to.be.undefined;
    expect(input.params.values).to.be.an('Array').that.has.length(1);
    expect(input.params.values[0]).to.have.property('value', 'Check this');
    expect(input.params.values[0]).to.have.property('is_checked', '1');
  });

  it('Should map multiple blocks at once', () => {
    const unknownBlock = {
      name: 'unknown',
      clientId: '1234',
      attributes: {
        id: 'unknowns',
      },
    };
    const inputs = formBlocksToBody([submitBlock, emailBlock, unknownBlock]);
    inputs.map(checkBodyInputBasics);
    expect(inputs.length).to.be.equal(2);
    expect(inputs[0].id).to.be.equal('submit');
    expect(inputs[0].position).to.be.equal('1');
    expect(inputs[1].id).to.be.equal('email');
    expect(inputs[1].position).to.be.equal('2');
  });
});
