import { expect } from 'chai';
import { partial, isEmpty, isUndefined } from 'lodash';
import { formBodyToBlocksFactory } from '../../../../assets/js/src/form_editor/store/form_body_to_blocks.jsx';

import {
  emailInput,
  firstNameInput,
  lastNameInput,
  segmentsInput,
  submitInput,
  customTextInput,
  customTextareaInput,
  customRadioInput,
  customSelectInput,
  customCheckboxInput,
  customDateInput,
  customHtml,
  divider,
  nestedColumns,
  headingInput,
  paragraphInput,
  image,
} from './form_to_block_test_data';

import {
  fontSizeDefinitions,
  colorDefinitions,
  gradientDefinitions,
} from './editor_settings';

const getMapper = partial(
  formBodyToBlocksFactory,
  fontSizeDefinitions,
  colorDefinitions,
  gradientDefinitions,
);
const formBodyToBlocks = getMapper([]);

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
    // @ts-expect-error Passing wrong type on purpose
    expect(() => formBodyToBlocks('hello')).to.throw(error);
    expect(() => formBodyToBlocks(undefined)).to.throw(error);
    // @ts-expect-error Passing wrong type on purpose
    expect(() => formBodyToBlocks(1)).to.throw(error);
  });

  it('Should throw an error for wrong custom fields input', () => {
    const error = 'Mapper expects customFields to be an array.';
    expect(() => getMapper(null)).to.throw(error);
    // @ts-expect-error Passing wrong type on purpose
    expect(() => getMapper('hello')).to.throw(error);
    // @ts-expect-error Passing wrong type on purpose
    expect(() => getMapper(() => {})).to.throw(error);
    // @ts-expect-error Passing wrong type on purpose
    expect(() => getMapper(1)).to.throw(error);
  });

  it('Should map email input to block', () => {
    const [block] = formBodyToBlocks([emailInput]);
    checkBlockBasics(block);
    expect(block.clientId).to.include('email_');
    expect(block.name).to.be.equal('mailpoet-form/email-input');
    expect(block.attributes.label).to.be.equal('Email');
    expect(block.attributes.labelWithinInput).to.be.equal(false);
  });

  it('Should add default styles to input blocks', () => {
    const customFieldText = {
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
    const customFieldTextarea = {
      created_at: '2019-12-10T15:05:06+00:00',
      id: 2,
      name: 'Custom Field 2 name',
      params: {
        label: 'Description',
        required: '1',
        validate: '',
        lines: '3',
      },
      type: 'textarea',
      updated_at: '2019-12-10T15:05:06+00:00',
    };
    const map = getMapper([customFieldText, customFieldTextarea]);
    const [email, firstName, lastName, customText, customTextArea, submit] =
      map([
        { ...emailInput, position: '1' },
        { ...firstNameInput, position: '2' },
        { ...lastNameInput, position: '3' },
        { ...customTextInput, position: '4' },
        { ...customTextareaInput, position: '5', id: 2 },
        { ...submitInput, position: '6' },
      ]);
    const defaultStyles = {
      fullWidth: false,
      inheritFromTheme: true,
    };
    expect(email.attributes.styles).to.eql(defaultStyles);
    expect(firstName.attributes.styles).to.eql(defaultStyles);
    expect(lastName.attributes.styles).to.eql(defaultStyles);
    expect(customText.attributes.styles).to.eql(defaultStyles);
    expect(customTextArea.attributes.styles).to.eql(defaultStyles);
    expect(submit.attributes.styles).to.eql(defaultStyles);
  });

  it('Should map input block styles', () => {
    const customFieldText = {
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

    const emailStyles = {
      full_width: '1',
    };

    const customTextStyles = {
      full_width: '0',
      bold: '1',
      background_color: '#ffffff',
      border_size: '4',
      border_radius: '20',
      border_color: '#cccccc',
    };

    const map = getMapper([customFieldText]);
    const [email, customText] = map([
      { ...emailInput, styles: emailStyles },
      { ...customTextInput, styles: customTextStyles },
    ]);
    expect(email.attributes.styles).to.eql({
      fullWidth: true,
      inheritFromTheme: true,
    });
    expect(customText.attributes.styles).to.eql({
      fullWidth: false,
      inheritFromTheme: false,
      bold: true,
      backgroundColor: '#ffffff',
      borderSize: 4,
      borderRadius: 20,
      borderColor: '#cccccc',
    });
  });

  it('Should map submit block styles', () => {
    const defaultSubmitStyles = {
      full_width: '1',
    };

    const styledSubmitStyles = {
      full_width: '0',
      bold: '1',
      background_color: '#ffffff',
      gradient: 'linear-gradient(#fff, #000)',
      border_size: '4',
      border_radius: '20',
      border_color: '#cccccc',
      font_size: '16',
      font_color: '#aaaaaa',
    };

    const [defaultSubmit, styledSubmit] = formBodyToBlocks([
      { ...submitInput, styles: defaultSubmitStyles },
      { ...submitInput, styles: styledSubmitStyles },
    ]);
    expect(defaultSubmit.attributes.styles).to.deep.equal({
      fullWidth: true,
      inheritFromTheme: true,
    });
    expect(styledSubmit.attributes.styles).to.deep.equal({
      fullWidth: false,
      inheritFromTheme: false,
      bold: true,
      backgroundColor: '#ffffff',
      gradient: 'linear-gradient(#fff, #000)',
      borderSize: 4,
      borderRadius: 20,
      borderColor: '#cccccc',
      fontSize: 16,
      fontColor: '#aaaaaa',
    });
  });

  it('Should map email with label within correctly', () => {
    const params = { label_within: '1' };
    const email = { ...emailInput, params };

    const [block] = formBodyToBlocks([email]);
    expect(block.attributes.labelWithinInput).to.be.equal(true);
  });

  it('Should add a label if label is missing in data', () => {
    const input = { ...emailInput };
    delete input.params.label;
    const [block] = formBodyToBlocks([{ ...emailInput }]);
    checkBlockBasics(block);
    expect(block.attributes.label).to.be.equal('');
  });

  it('Should map first name input to block', () => {
    const [block] = formBodyToBlocks([firstNameInput]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.include('first_name_');
    expect(block.name).to.be.equal('mailpoet-form/first-name-input');
    expect(block.attributes.label).to.be.equal('First Name');
    expect(block.attributes.labelWithinInput).to.be.equal(false);
    expect(block.attributes.mandatory).to.be.equal(false);
  });

  it('Should map first name with label within correctly', () => {
    const params = {
      label_within: '1',
      required: '1',
    };
    const input = { ...firstNameInput, params };
    const [block] = formBodyToBlocks([input]);
    expect(block.attributes.labelWithinInput).to.be.equal(true);
    expect(block.attributes.mandatory).to.be.equal(true);
  });

  it('Should map last name input to block', () => {
    const [block] = formBodyToBlocks([lastNameInput]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.include('last_name_');
    expect(block.name).to.be.equal('mailpoet-form/last-name-input');
    expect(block.attributes.label).to.be.equal('Last Name');
    expect(block.attributes.labelWithinInput).to.be.equal(false);
    expect(block.attributes.mandatory).to.be.equal(false);
  });

  it('Should map last name with label within correctly', () => {
    const params = {
      label_within: '1',
      required: '1',
    };
    const input = { ...lastNameInput, params };
    const [block] = formBodyToBlocks([input]);
    expect(block.attributes.labelWithinInput).to.be.equal(true);
    expect(block.attributes.mandatory).to.be.equal(true);
  });

  it('Should map segments input to block', () => {
    const [block] = formBodyToBlocks([segmentsInput]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.include('segments_');
    expect(block.name).to.be.equal('mailpoet-form/segment-select');
    expect(block.attributes.label).to.be.equal('Select list(s):');
    expect(block.attributes.values).to.be.an('Array');
    expect(block.attributes.values[0]).to.haveOwnProperty('id', '6');
    expect(block.attributes.values[1]).to.haveOwnProperty('isChecked', true);
  });

  it('Should map segments input without values to block', () => {
    const input = { ...segmentsInput };
    input.params.values = undefined;
    const [block] = formBodyToBlocks([input]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.include('segments_');
    expect(block.attributes.values).to.be.an('Array');
    expect(block.attributes.values).to.have.length(0);
  });

  it('Should map submit button to block', () => {
    const [block] = formBodyToBlocks([submitInput]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.include('submit_');
    expect(block.name).to.be.equal('mailpoet-form/submit-button');
    expect(block.attributes.label).to.be.equal('Subscribe!');
  });

  it('Should map divider to block', () => {
    const [block] = formBodyToBlocks([divider]);
    checkBlockBasics(block);
    expect(block.attributes.height).to.be.equal(12);
    expect(block.attributes.type).to.be.equal('spacer');
    expect(block.attributes.style).to.be.equal('dotted');
    expect(block.attributes.dividerHeight).to.be.equal(23);
    expect(block.attributes.dividerWidth).to.be.equal(34);
    expect(block.attributes.color).to.be.equal('red');
  });

  it('Should map dividers to blocks', () => {
    const [block1, block2] = formBodyToBlocks([
      { ...divider },
      { ...divider, position: '2' },
    ]);
    checkBlockBasics(block1);
    expect(block1.clientId).to.be.include('divider_');
    expect(block1.name).to.be.equal('mailpoet-form/divider');
    checkBlockBasics(block2);
    expect(block2.clientId).to.be.include('divider_');
    expect(block2.name).to.be.equal('mailpoet-form/divider');
  });

  it('Should map custom html to blocks', () => {
    const [block1, block2] = formBodyToBlocks([
      { ...customHtml, params: { text: '123', nl2br: '1' } },
      { ...customHtml, position: '2', params: { text: 'nice one' } },
    ]);
    checkBlockBasics(block1);
    expect(block1.clientId).to.be.include('html_');
    expect(block1.name).to.be.equal('mailpoet-form/html');
    expect(block1.attributes.content).to.be.equal('123');
    expect(block1.attributes.nl2br).to.be.equal(true);
    checkBlockBasics(block2);
    expect(block2.clientId).to.be.include('html_');
    expect(block2.name).to.be.equal('mailpoet-form/html');
    expect(block2.attributes.content).to.be.equal('nice one');
    expect(block2.attributes.nl2br).to.be.equal(false);
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
    const map = getMapper([customField]);
    const [block] = map([customTextInput]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.include('1_');
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
        values: [{ value: 'option 1' }, { value: 'option 2' }],
      },
      type: 'radio',
      updated_at: '2019-12-10T15:05:06+00:00',
    };
    const map = getMapper([customField]);
    const [block] = map([customRadioInput]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.include('3_');
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
    const map = getMapper([customField]);
    const [block] = map([customCheckboxInput]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.include('4_');
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
    const map = getMapper([customField]);
    const [block] = map([customSelectInput]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.include('5_');
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

    const map = getMapper([customField]);
    const [block] = map([customDateInput]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.include('6_');
    expect(block.name).to.be.equal('mailpoet-form/custom-date-customdate');
    expect(block.attributes.label).to.be.equal('Date');
    expect(block.attributes.mandatory).to.be.equal(true);
    expect(block.attributes.dateFormat).to.be.equal('MM/YYYY');
    expect(block.attributes.dateType).to.be.equal('month_year');
    expect(block.attributes.defaultToday).to.be.equal(true);
  });

  it('Should ignore unknown input type', () => {
    const blocks = formBodyToBlocks([{ ...submitInput, id: 'some-nonsense' }]);
    expect(isEmpty(blocks)).to.be.equal(true);
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

  it('Should map nested columns', () => {
    const email = { ...emailInput };
    const nested = { ...nestedColumns, position: '2' };
    const unknown = { id: 'unknown', position: '3' };
    const blocks = formBodyToBlocks([email, nested, unknown]);
    expect(blocks.length).to.be.equal(2);
    expect(blocks[1].name).to.be.equal('core/columns');
    expect(blocks[1].attributes.verticalAlignment).to.be.equal('center');
    expect(blocks[1].attributes.isStackedOnMobile).to.be.equal(false);
    expect(blocks[1].attributes.style.spacing.padding).to.be.deep.equal({
      top: '1em',
      right: '2em',
      bottom: '3em',
      left: '4em',
    });
    // First level
    const column1 = blocks[1].innerBlocks[0];
    expect(column1.name).to.be.equal('core/column');
    expect(column1.attributes.width).to.be.equal('66.66%');
    expect(column1.attributes.verticalAlignment).to.be.equal('center');
    expect(column1.attributes.style.spacing.padding).to.be.deep.equal({
      top: '10px',
      right: '20px',
      bottom: '30px',
      left: '40px',
    });
    expect(column1.innerBlocks.length).to.be.equal(2);
    const columns11 = column1.innerBlocks[0];
    checkBlockBasics(column1.innerBlocks[1]);
    const column2 = blocks[1].innerBlocks[1];
    expect(column2.name).to.be.equal('core/column');
    expect(column2.innerBlocks.length).to.be.equal(1);
    checkBlockBasics(column1.innerBlocks[0]);
    // Second level
    expect(columns11.innerBlocks.length).to.be.equal(2);
    expect(columns11.attributes.isStackedOnMobile).to.be.equal(true);
    const column11 = columns11.innerBlocks[0];
    expect(column11.innerBlocks.length).to.be.equal(1);
    expect(column11.attributes.width).to.be.equal('50rem');
    checkBlockBasics(column11.innerBlocks[0]);
    const column12 = columns11.innerBlocks[1];
    expect(column12.innerBlocks.length).to.be.equal(0);
    expect(column12.attributes.width).to.be.equal('50%');
  });

  it('Should map columns colors', () => {
    const params = {
      text_color: '#ffffff',
      background_color: '#000000',
    };
    const nested = { ...nestedColumns, params };

    const [block] = formBodyToBlocks([nested]);
    expect(block.attributes.textColor).to.be.equal('white');
    expect(block.attributes.backgroundColor).to.be.equal('black');
    expect(isUndefined(block.attributes.style.color.text)).to.be.equal(true);
    expect(isUndefined(block.attributes.style.color.background)).to.be.equal(
      true,
    );

    nested.params = {
      text_color: '#aaaaaa',
      background_color: '#bbbbbb',
    };
    const [block2] = formBodyToBlocks([nested]);
    expect(isUndefined(block2.attributes.textColor)).to.be.equal(true);
    expect(isUndefined(block2.attributes.backgroundColor)).to.be.equal(true);
    expect(block2.attributes.style.color.text).to.be.equal('#aaaaaa');
    expect(block2.attributes.style.color.background).to.be.equal('#bbbbbb');
  });

  it('Should map columns gradient', () => {
    const params = {
      gradient:
        'linear-gradient(90deg, rgba(0,0,0,1) 0%, rgba(255,255,255,1) 100%)',
    };
    const nested = { ...nestedColumns, params };

    const [block] = formBodyToBlocks([nested]);
    expect(block.attributes.gradient).to.be.equal('black-white');
    expect(isUndefined(block.attributes.style.color.gradient)).to.be.equal(
      true,
    );

    nested.params = {
      gradient:
        'linear-gradient(95deg, rgba(0,0,0,1) 0%, rgba(255,255,255,1) 100%)',
    };
    const [block2] = formBodyToBlocks([nested]);
    expect(isUndefined(block2.attributes.gradient)).to.be.equal(true);
    expect(block2.attributes.style.color.gradient).to.be.equal(
      'linear-gradient(95deg, rgba(0,0,0,1) 0%, rgba(255,255,255,1) 100%)',
    );
  });

  it('Should map class name', () => {
    const params = {
      class_name: 'custom-class',
    };
    const nested = { ...nestedColumns, params };

    const [block] = formBodyToBlocks([nested]);
    expect(block.attributes.className).to.be.equal('custom-class');

    const params2 = {
      class_name: 'custom-class-2',
    };
    const email = { ...emailInput, position: '1', params: params2 };
    const [mappedEmail] = formBodyToBlocks([email]);
    expect(mappedEmail.attributes.className).to.be.equal('custom-class-2');

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
    const params3 = {
      class_name: 'custom-class-3 custom-class-4',
    };
    const customText = { ...customTextInput, position: '1', params: params3 };
    const map = getMapper([customField]);
    const [mappedCustomText] = map([customText]);
    expect(mappedCustomText.attributes.className).to.be.equal(
      'custom-class-3 custom-class-4',
    );
  });

  it('It should map heading', () => {
    const heading = { ...headingInput };

    const [block] = formBodyToBlocks([heading]);
    expect(block.attributes.content).to.be.equal('');
    expect(block.attributes.level).to.be.equal(2);
    expect(isUndefined(block.attributes.align)).to.be.equal(true);
  });

  it('It should map heading font size', () => {
    const heading = { ...headingInput, params: { font_size: 13 } };

    const [block] = formBodyToBlocks([heading]);
    expect(block.attributes.fontSize).to.equal('small');
  });

  it('It should map heading custom font size and line height', () => {
    const heading = {
      ...headingInput,
      params: { font_size: '34', line_height: '1.5' },
    };

    const [block] = formBodyToBlocks([heading]);
    expect(isUndefined(block.attributes.fontSize)).to.be.equal(true);
    expect(block.attributes.style.typography.fontSize).to.eq(34);
    expect(block.attributes.style.typography.lineHeight).to.eq('1.5');
  });

  it('It should map paragraph', () => {
    const paragraph = { ...paragraphInput };

    const [block] = formBodyToBlocks([paragraph]);
    expect(block.name).to.equal('core/paragraph');
    expect(block.attributes.content).to.equal('content');
    expect(block.attributes.dropCap).to.equal(true);
    expect(block.attributes.align).to.equal('center');
    expect(block.attributes.className).to.equal('class name');
  });

  it('It should map paragraph font size', () => {
    const heading = { ...paragraphInput, params: { font_size: 13 } };

    const [block] = formBodyToBlocks([heading]);
    expect(block.attributes.fontSize).to.equal('small');
  });

  it('It should map paragraph custom font size and line height', () => {
    const heading = {
      ...paragraphInput,
      params: { font_size: '34', line_height: '1.5' },
    };

    const [block] = formBodyToBlocks([heading]);
    expect(isUndefined(block.attributes.fontSize)).to.be.equal(true);
    expect(block.attributes.style.typography.fontSize).to.eq(34);
    expect(block.attributes.style.typography.lineHeight).to.eq('1.5');
  });

  it('It should map heading with data', () => {
    const heading = {
      ...headingInput,
      position: '1',
      params: {
        text_color: '#f78da7',
        content: 'Content',
        level: '1',
        anchor: 'anchor',
        align: 'right',
        class_name: 'class',
      },
    };

    const [block] = formBodyToBlocks([heading]);
    expect(block.attributes.content).to.be.equal('Content');
    expect(block.attributes.level).to.be.equal(1);
    expect(block.attributes.textAlign).to.be.equal('right');
    expect(block.attributes.className).to.be.equal('class');
    expect(block.attributes.anchor).to.be.equal('anchor');
    expect(block.attributes.style.color.text).to.be.equal('#f78da7');
  });

  it('It should map image', () => {
    const [block] = formBodyToBlocks([image]);
    expect(block.name).to.equal('core/image');
    expect(block.attributes.className).to.equal('my-class');
    expect(block.attributes.align).to.equal('center');
    expect(block.attributes.url).to.equal('http://example.com/image.jpg');
    expect(block.attributes.alt).to.equal('Alt text');
    expect(block.attributes.title).to.equal('Title');
    expect(block.attributes.caption).to.equal('Caption');
    expect(block.attributes.linkDestination).to.equal('none');
    expect(block.attributes.link).to.equal('http://example.com');
    expect(block.attributes.href).to.be.equal('http://example.com/link');
    expect(block.attributes.linkClass).to.be.equal('link-class');
    expect(block.attributes.rel).to.be.equal('linkRel');
    expect(block.attributes.linkTarget).to.be.equal('_blank');
    expect(block.attributes.id).to.equal(123);
    expect(block.attributes.sizeSlug).to.equal('medium');
    expect(block.attributes.width).to.equal(100);
    expect(block.attributes.height).to.equal(200);
  });

  it('Should map custom field in column', () => {
    const columnsWithCustomField = { ...nestedColumns };
    // Add custom field to first column
    columnsWithCustomField.body[0].body = [customTextInput];
    // Prepare custom fields definitions
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
    const customFields = [customField];
    // Map columns with custom field
    const map = getMapper(customFields);
    const [columns] = map([columnsWithCustomField]);
    const firstColumn = columns.innerBlocks[0];
    expect(firstColumn.innerBlocks.length).to.be.equal(1);
    const customFieldBlock = firstColumn.innerBlocks[0];
    checkBlockBasics(customFieldBlock);
    expect(customFieldBlock.name).includes('mailpoet-form/custom-text-');
  });
});
