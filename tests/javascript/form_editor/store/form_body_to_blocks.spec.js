import { expect } from 'chai';
import formBodyToBlocks from '../../../../assets/js/src/form_editor/store/form_body_to_blocks.jsx';

const emailInput = {
  type: 'text',
  name: 'Email',
  id: 'email',
  unique: '0',
  static: '1',
  params: {
    label: 'Email',
  },
  position: null,
};
const firstNameInput = {
  type: 'text',
  name: 'First name',
  id: 'first_name',
  unique: '1',
  static: '0',
  params: {
    label: 'First Name',
  },
  position: null,
};
const lastNameInput = {
  type: 'text',
  name: 'Last name',
  id: 'last_name',
  unique: '1',
  static: '0',
  params: {
    label: 'Last Name',
  },
  position: null,
};
const segmentsInput = {
  type: 'segment',
  name: 'List selection',
  id: 'segments',
  unique: '1',
  static: '0',
  params: {
    label: 'Select list(s):',
    values: [
      {
        id: '6',
        name: 'Unicorn Truthers',
      },
      {
        id: '24',
        is_checked: '1',
        name: 'Carrots are lit',
      },
      {
        id: '29',
        name: 'Daily',
      },
    ],
  },
  position: null,
};
const submitInput = {
  type: 'submit',
  name: 'Submit',
  id: 'submit',
  unique: '0',
  static: '1',
  params: {
    label: 'Subscribe!',
  },
  position: null,
};
const customTextInput = {
  type: 'text',
  name: 'Street name',
  id: '1',
  unique: '1',
  static: '0',
  params: {
    required: '',
    validate: 'alphanum',
    label: 'Name of the street',
    label_within: '1',
  },
  position: null,
};
const customRadioInput = {
  type: 'radio',
  name: 'Options',
  id: '3',
  unique: '1',
  static: '0',
  params: {
    required: '',
    label: 'Options',
    hide_label: '1',
    values: [
      {
        value: 'option 1',
      },
    ],
  },
  position: null,
};
const customSelectInput = {
  type: 'select',
  name: 'Custom select',
  id: '5',
  unique: '1',
  static: '0',
  params: {
    required: '',
    label: 'Select',
    label_within: '1',
    values: [
      {
        value: 'option 1',
      },
    ],
  },
  position: null,
};
const customCheckboxInput = {
  type: 'checkbox',
  name: 'Custom check',
  id: '4',
  unique: '1',
  static: '0',
  params: {
    required: '',
    label: 'Check this',
    hide_label: '',
    values: [
      {
        value: 'Check',
        is_checked: '1',
      },
    ],
  },
  position: null,
};
const customDateInput = {
  type: 'date',
  name: 'Custom date',
  id: '6',
  unique: '1',
  static: '0',
  params: {
    required: '1',
    label: 'Date',
    date_type: 'month_year',
    date_format: 'MM/YYYY',
    is_default_today: true,
  },
  position: null,
};
const divider = {
  type: 'divider',
  name: 'Divider',
  id: 'divider',
  unique: '0',
  static: '0',
  params: '',
  position: null,
};

const customHtml = {
  type: 'html,',
  name: 'Custom text or HTML',
  id: 'html',
  unique: '0',
  static: '0',
  params: {
    text: 'test',
    nl2br: '1',
  },
  position: null,
};

const checkBlockBasics = (block) => {
  expect(block.clientId).to.be.a('string');
  expect(block.name).to.be.a('string');
  expect(block.isValid).to.be.equal(true);
  expect(block.innerBlocks).to.be.a('Array');
  expect(block.attributes).to.be.a('Object');
};

describe('Form Body To Blocks', () => {
  it('Should throw an error for wrong input', () => {
    const error = 'Mapper expects form body to be an array.';
    expect(() => formBodyToBlocks(null)).to.throw(error);
    expect(() => formBodyToBlocks('hello')).to.throw(error);
    expect(() => formBodyToBlocks(undefined)).to.throw(error);
    expect(() => formBodyToBlocks(1)).to.throw(error);
  });

  it('Should throw an error for wrong custom fields input', () => {
    const error = 'Mapper expects customFields to be an array.';
    expect(() => formBodyToBlocks([], null)).to.throw(error);
    expect(() => formBodyToBlocks([], 'hello')).to.throw(error);
    expect(() => formBodyToBlocks([], () => {})).to.throw(error);
    expect(() => formBodyToBlocks([], 1)).to.throw(error);
  });

  it('Should map email input to block', () => {
    const [block] = formBodyToBlocks([{ ...emailInput, position: '1' }]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('email_0');
    expect(block.name).to.be.equal('mailpoet-form/email-input');
    expect(block.attributes.label).to.be.equal('Email');
    expect(block.attributes.labelWithinInput).to.be.equal(false);
  });

  it('Should map email with label within correctly', () => {
    const email = { ...emailInput, position: '1' };
    email.params.label_within = '1';
    const [block] = formBodyToBlocks([email]);
    expect(block.attributes.labelWithinInput).to.be.equal(true);
  });

  it('Should add a label if label is missing in data', () => {
    const input = { ...emailInput, position: '1' };
    delete input.params.label;
    const [block] = formBodyToBlocks([{ ...emailInput, position: '1' }]);
    checkBlockBasics(block);
    expect(block.attributes.label).to.be.equal(null);
  });

  it('Should map first name input to block', () => {
    const [block] = formBodyToBlocks([{ ...firstNameInput, position: '1' }]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('first_name_0');
    expect(block.name).to.be.equal('mailpoet-form/first-name-input');
    expect(block.attributes.label).to.be.equal('First Name');
    expect(block.attributes.labelWithinInput).to.be.equal(false);
    expect(block.attributes.mandatory).to.be.equal(false);
  });

  it('Should map first name with label within correctly', () => {
    const input = { ...firstNameInput, position: '1' };
    input.params.label_within = '1';
    input.params.required = '1';
    const [block] = formBodyToBlocks([input]);
    expect(block.attributes.labelWithinInput).to.be.equal(true);
    expect(block.attributes.mandatory).to.be.equal(true);
  });

  it('Should map last name input to block', () => {
    const [block] = formBodyToBlocks([{ ...lastNameInput, position: '1' }]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('last_name_0');
    expect(block.name).to.be.equal('mailpoet-form/last-name-input');
    expect(block.attributes.label).to.be.equal('Last Name');
    expect(block.attributes.labelWithinInput).to.be.equal(false);
    expect(block.attributes.mandatory).to.be.equal(false);
  });

  it('Should map last name with label within correctly', () => {
    const input = { ...lastNameInput, position: '1' };
    input.params.label_within = '1';
    input.params.required = '1';
    const [block] = formBodyToBlocks([input]);
    expect(block.attributes.labelWithinInput).to.be.equal(true);
    expect(block.attributes.mandatory).to.be.equal(true);
  });

  it('Should map segments input to block', () => {
    const [block] = formBodyToBlocks([{ ...segmentsInput, position: '1' }]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('segments_0');
    expect(block.name).to.be.equal('mailpoet-form/segment-select');
    expect(block.attributes.label).to.be.equal('Select list(s):');
    expect(block.attributes.values).to.be.an('Array');
    expect(block.attributes.values[0]).to.haveOwnProperty('id', '6');
    expect(block.attributes.values[0]).to.haveOwnProperty('name', 'Unicorn Truthers');
    expect(block.attributes.values[1]).to.haveOwnProperty('isChecked', true);
  });

  it('Should map segments input without values to block', () => {
    const input = { ...segmentsInput, position: '1' };
    input.params.values = undefined;
    const [block] = formBodyToBlocks([input]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('segments_0');
    expect(block.attributes.values).to.be.an('Array');
    expect(block.attributes.values).to.have.length(0);
  });

  it('Should map submit button to block', () => {
    const [block] = formBodyToBlocks([{ ...submitInput, position: '1' }]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('submit_0');
    expect(block.name).to.be.equal('mailpoet-form/submit-button');
    expect(block.attributes.label).to.be.equal('Subscribe!');
  });

  it('Should map dividers to blocks', () => {
    const [block1, block2] = formBodyToBlocks([
      { ...divider, position: '1' },
      { ...divider, position: '2' },
    ]);
    checkBlockBasics(block1);
    expect(block1.clientId).to.be.equal('divider_0');
    expect(block1.name).to.be.equal('mailpoet-form/divider');
    checkBlockBasics(block2);
    expect(block2.clientId).to.be.equal('divider_1');
    expect(block2.name).to.be.equal('mailpoet-form/divider');
  });

  it('Should map custom html to blocks', () => {
    const [block1, block2] = formBodyToBlocks([
      { ...customHtml, position: '1', params: { text: '123', nl2br: '1' } },
      { ...customHtml, position: '2', params: { text: 'nice one' } },
    ]);
    checkBlockBasics(block1);
    expect(block1.clientId).to.be.equal('html_0');
    expect(block1.name).to.be.equal('mailpoet-form/html');
    expect(block1.attributes.content).to.be.equal('123');
    expect(block1.attributes.nl2br).to.be.true;
    checkBlockBasics(block2);
    expect(block2.clientId).to.be.equal('html_1');
    expect(block2.name).to.be.equal('mailpoet-form/html');
    expect(block2.attributes.content).to.be.equal('nice one');
    expect(block2.attributes.nl2br).to.be.false;
  });

  it('Should map custom text input to block', () => {
    const customField = {
      created_at: '2019-12-10T15:05:06+00:00',
      id: 1,
      name: 'Custom Field ^name',
      params: {
        label: 'Street name',
        required: '1',
        validate: '',
      },
      type: 'text',
      updated_at: '2019-12-10T15:05:06+00:00',
    };
    const [block] = formBodyToBlocks([{ ...customTextInput, position: '1' }], [customField]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('1_0');
    expect(block.name).to.be.equal('mailpoet-form/custom-text-customfieldname');
    expect(block.attributes.label).to.be.equal('Name of the street');
    expect(block.attributes.mandatory).to.be.equal(false);
    expect(block.attributes.labelWithinInput).to.be.equal(true);
    expect(block.attributes.validate).to.be.equal('alphanum');
  });

  it('Should map custom radio input to block', () => {
    const customField = {
      created_at: '2019-12-10T15:05:06+00:00',
      id: 3,
      name: 'Name',
      params: {
        required: '1',
        label: 'Options 123',
        hide_label: '',
        values: [
          { value: 'option 1' },
          { value: 'option 2' },
        ],
      },
      type: 'radio',
      updated_at: '2019-12-10T15:05:06+00:00',
    };
    const [block] = formBodyToBlocks([{ ...customRadioInput, position: '1' }], [customField]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('3_0');
    expect(block.name).to.be.equal('mailpoet-form/custom-radio-name');
    expect(block.attributes.label).to.be.equal('Options');
    expect(block.attributes.mandatory).to.be.equal(false);
    expect(block.attributes.hideLabel).to.be.equal(true);
    expect(block.attributes.values).to.be.an('Array').that.has.length(1);
    expect(block.attributes.values[0]).to.have.property('name', 'option 1');
  });

  it('Should map custom checkbox input to block', () => {
    const customField = {
      type: 'checkbox',
      name: 'Custom check',
      id: 4,
      params: {
        required: '',
        label: 'Check this',
        hide_label: '',
        values: [
          {
            value: 'Check',
            is_checked: '1',
          },
        ],
      },
      position: null,
    };
    const [block] = formBodyToBlocks([{ ...customCheckboxInput, position: '1' }], [customField]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('4_0');
    expect(block.name).to.be.equal('mailpoet-form/custom-checkbox-customcheck');
    expect(block.attributes.label).to.be.equal('Check this');
    expect(block.attributes.mandatory).to.be.equal(false);
    expect(block.attributes.hideLabel).to.be.equal(false);
    expect(block.attributes.values).to.be.an('Array').that.has.length(1);
    expect(block.attributes.values[0]).to.have.property('name', 'Check');
    expect(block.attributes.values[0]).to.have.property('isChecked', true);
  });

  it('Should map custom select input to block', () => {
    const customField = {
      type: 'select',
      name: 'Custom select',
      id: 5,
      params: {
        required: '',
        label: 'Select',
        values: [
          {
            value: 'option 1',
          },
        ],
      },
      position: null,
    };
    const [block] = formBodyToBlocks([{ ...customSelectInput, position: '1' }], [customField]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('5_0');
    expect(block.name).to.be.equal('mailpoet-form/custom-select-customselect');
    expect(block.attributes.label).to.be.equal('Select');
    expect(block.attributes.mandatory).to.be.equal(false);
    expect(block.attributes.labelWithinInput).to.be.equal(true);
    expect(block.attributes.values).to.be.an('Array').that.has.length(1);
    expect(block.attributes.values[0]).to.have.property('name', 'option 1');
  });

  it('Should map custom date input to block', () => {
    const customField = {
      created_at: '2019-12-13T15:22:07+00:00',
      id: 6,
      name: 'Custom Date',
      params: {
        required: '1',
        is_default_today: '1',
        date_type: 'month_year',
        date_format: 'YYYY/MM',
      },
      type: 'date',
      updated_at: '2019-12-13T15:22:07+00:00',
    };
    const [block] = formBodyToBlocks([{ ...customDateInput, position: '1' }], [customField]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('6_0');
    expect(block.name).to.be.equal('mailpoet-form/custom-date-customdate');
    expect(block.attributes.label).to.be.equal('Date');
    expect(block.attributes.mandatory).to.be.true;
    expect(block.attributes.dateFormat).to.be.equal('MM/YYYY');
    expect(block.attributes.dateType).to.be.equal('month_year');
    expect(block.attributes.defaultToday).to.be.true;
  });

  it('Should ignore unknown input type', () => {
    const blocks = formBodyToBlocks([{ ...submitInput, id: 'some-nonsense' }]);
    expect(blocks).to.be.empty;
  });

  it('Should map more inputs at once', () => {
    const email = { ...emailInput, position: '2' };
    const submit = { ...submitInput, position: '2' };
    const unknown = { id: 'unknown', position: '3' };
    const blocks = formBodyToBlocks([email, submit, unknown]);
    expect(blocks.length).to.be.equal(2);
    blocks.map(checkBlockBasics);
    expect(blocks[0].name).to.be.equal('mailpoet-form/email-input');
    expect(blocks[1].name).to.be.equal('mailpoet-form/submit-button');
  });
});
