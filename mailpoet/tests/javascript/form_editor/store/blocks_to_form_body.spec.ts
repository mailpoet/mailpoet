import { expect } from 'chai';
import { partial, isEmpty, isUndefined } from 'lodash';
import { blocksToFormBodyFactory } from '../../../../assets/js/src/form_editor/store/blocks_to_form_body';
import {
  emailBlock,
  lastNameBlock,
  firstNameBlock,
  submitBlock,
  segmentsBlock,
  customTextBlock,
  customRadioBlock,
  customCheckBox,
  customDateBlock,
  customHtmlBlock,
  customSelectBlock,
  dividerBlock,
  nestedColumns,
  headingBlock,
  paragraphBlock,
  imageBlock,
} from './block_to_form_test_data';

import {
  fontSizeDefinitions,
  colorDefinitions,
  gradientDefinitions,
} from './editor_settings';

const checkBodyInputBasics = (input) => {
  expect(input.id).to.be.a('string');
  expect(input.type).to.be.a('string');
  expect(isEmpty(input.type)).to.be.equal(false);
};

const getMapper = partial(
  blocksToFormBodyFactory,
  fontSizeDefinitions,
  colorDefinitions,
  gradientDefinitions,
);
const formBlocksToBody = getMapper([]);

describe('Blocks to Form Body', () => {
  it('Should throw an error for wrong input', () => {
    const error = 'Mapper expects blocks to be an array.';
    expect(() => formBlocksToBody(null)).to.throw(error);
    // @ts-expect-error - testing wrong input
    expect(() => formBlocksToBody('hello')).to.throw(error);
    expect(() => formBlocksToBody(undefined)).to.throw(error);
    // @ts-expect-error - testing wrong input
    expect(() => formBlocksToBody(1)).to.throw(error);
  });

  it('Should map email block to input data', () => {
    const [input] = formBlocksToBody([emailBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('email');
    expect(input.name).to.be.equal('Email');
    expect(input.type).to.be.equal('text');
    expect(input.params.label).to.be.equal('Email Address');
    expect(input.params.required).to.be.equal('1');
    expect(isUndefined(input.params.label_within)).to.be.equal(true);
  });

  it('Should map email block with label within', () => {
    const block = { ...emailBlock };
    block.attributes.labelWithinInput = true;
    const [input] = formBlocksToBody([block]);
    checkBodyInputBasics(input);
    expect(input.params.label_within).to.be.equal('1');
  });

  it('Should map input block styles', () => {
    const blockWithThemeStyles = {
      ...emailBlock,
      attributes: {
        ...emailBlock.attributes,
        styles: {
          fullWidth: true,
          inheritFromTheme: true,
          bold: true,
        },
      },
    };
    const blockWithCustomStyles = {
      ...firstNameBlock,
      attributes: {
        ...firstNameBlock.attributes,
        styles: {
          fullWidth: false,
          inheritFromTheme: false,
          bold: true,
          backgroundColor: '#aaaaaa',
          borderRadius: 23,
          borderSize: 4,
          borderColor: '#dddddd',
        },
      },
    };
    const [inputWithThemeStyles, inputWithCustomStyles] = formBlocksToBody([
      blockWithThemeStyles,
      blockWithCustomStyles,
    ]);
    expect(inputWithThemeStyles.styles).to.eql({
      full_width: '1',
    });
    expect(inputWithCustomStyles.styles).to.eql({
      full_width: '0',
      bold: '1',
      background_color: '#aaaaaa',
      border_radius: 23,
      border_size: 4,
      border_color: '#dddddd',
    });
  });

  it('Should map submit block styles', () => {
    const blockWithThemeStyles = {
      ...submitBlock,
      attributes: {
        ...submitBlock.attributes,
        styles: {
          fullWidth: true,
          inheritFromTheme: true,
          bold: true,
        },
      },
    };
    const blockWithCustomStyles = {
      ...submitBlock,
      attributes: {
        ...submitBlock.attributes,
        styles: {
          fullWidth: false,
          inheritFromTheme: false,
          bold: true,
          backgroundColor: '#aaaaaa',
          gradient: 'linear-gradient(#fff, #000)',
          fontSize: 16,
          fontColor: '#cccccc',
          borderRadius: 23,
          borderSize: 4,
          borderColor: '#dddddd',
        },
      },
    };
    const [inputWithThemeStyles, inputWithCustomStyles] = formBlocksToBody([
      blockWithThemeStyles,
      blockWithCustomStyles,
    ]);
    expect(inputWithThemeStyles.styles).to.deep.equal({
      full_width: '1',
    });
    expect(inputWithCustomStyles.styles).to.deep.equal({
      full_width: '0',
      bold: '1',
      font_color: '#cccccc',
      font_size: 16,
      background_color: '#aaaaaa',
      gradient: 'linear-gradient(#fff, #000)',
      border_radius: 23,
      border_size: 4,
      border_color: '#dddddd',
    });
  });

  it('Should map last name block to input data', () => {
    const [input] = formBlocksToBody([lastNameBlock]);
    checkBodyInputBasics(input);
    const id = input.id;
    expect(id).to.be.equal('last_name');
    expect(input.name).to.be.equal('Last name');
    expect(input.type).to.be.equal('text');
    expect(input.params.label).to.be.equal('Last Name');
    expect(isUndefined(input.params.required)).to.be.equal(true);
    expect(isUndefined(input.params.label_within)).to.be.equal(true);
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
    expect(input.params.label).to.be.equal('First Name');
    expect(isUndefined(input.params.required)).to.be.equal(true);
    expect(isUndefined(input.params.label_within)).to.be.equal(true);
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
    expect(input.params.values[0]).to.have.property('id', '6');
    expect(input.params.values[1]).to.have.property('is_checked', '1');
  });

  it('Should map submit block to input data', () => {
    const [input] = formBlocksToBody([submitBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('submit');
    expect(input.name).to.be.equal('Submit');
    expect(input.type).to.be.equal('submit');
    expect(input.params.label).to.be.equal('Subscribe!');
    expect(input.styles).to.deep.equal({
      full_width: '0',
    });
  });

  it('Should map divider block to input data', () => {
    const [divider] = formBlocksToBody([dividerBlock]);
    checkBodyInputBasics(divider);
    expect(divider.id).to.be.equal('divider');
    expect(divider.name).to.be.equal('Divider');
    expect(divider.type).to.be.equal('divider');
    expect(divider.params).to.deep.equal({
      class_name: null,
      color: 'red',
      divider_height: 34,
      divider_width: 65,
      height: 23,
      style: 'solid',
      type: 'divider',
    });
  });

  it('Should map multiple dividers', () => {
    const [divider1, divider2] = formBlocksToBody([dividerBlock, dividerBlock]);
    checkBodyInputBasics(divider1);
    checkBodyInputBasics(divider2);
    expect(divider1.id).to.be.equal('divider');
    expect(divider2.id).to.be.equal('divider');
  });

  it('Should custom html block to form data', () => {
    const [html] = formBlocksToBody([customHtmlBlock]);
    checkBodyInputBasics(html);
    expect(html.id).to.be.equal('html');
    expect(html.name).to.be.equal('Custom text or HTML');
    expect(html.type).to.be.equal('html');
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
    const map = getMapper([customField]);
    const [input] = map([customTextBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('1');
    expect(input.name).to.be.equal('Custom Field name');
    expect(input.type).to.be.equal('text');
    expect(input.params.label).to.be.equal('Name of the street');
    expect(isUndefined(input.params.required)).to.be.equal(true);
    expect(isUndefined(input.params.label_within)).to.be.equal(true);
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
        values: [{ value: 'option 1' }],
      },
      type: 'select',
      updated_at: '2019-12-10T15:05:06+00:00',
    };

    const map = getMapper([customField]);
    const [input] = map([customSelectBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('6');
    expect(input.name).to.be.equal('Custom Select');
    expect(input.type).to.be.equal('select');
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
        values: [{ value: 'option 1' }],
      },
      type: 'radio',
      updated_at: '2019-12-10T15:05:06+00:00',
    };
    const map = getMapper([customField]);
    const [input] = map([customRadioBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('2');
    expect(input.name).to.be.equal('Custom Field name');
    expect(input.type).to.be.equal('radio');
    expect(input.params.label).to.be.equal('Options');
    expect(input.params.required).to.be.eq('1');
    expect(input.params.hide_label).to.eq('1');
    expect(input.params.values).to.be.an('Array').that.has.length(2);
    expect(input.params.values[0]).to.have.property('value', 'option 1');
    expect(input.params.values[1]).to.have.property('value', 'option 2');
  });

  it('Should map paragraph block', () => {
    const [input] = formBlocksToBody([paragraphBlock]);
    expect(input.type).to.be.equal('paragraph');
    expect(input.id).to.be.a('string');
    expect(input.params.content).to.be.equal('content');
    expect(input.params.drop_cap).to.be.equal('1');
    expect(input.params.align).to.be.equal('center');
  });

  it('Should map font size in paragraph block', () => {
    const [input] = formBlocksToBody([
      {
        ...paragraphBlock,
        attributes: {
          fontSize: 'small',
        },
      },
    ]);
    expect(input.params.font_size).to.be.equal(13);
  });

  it('Should map custom font size and line height in paragraph block', () => {
    const [input] = formBlocksToBody([
      {
        ...paragraphBlock,
        attributes: {
          fontSize: undefined,
          style: {
            typography: {
              fontSize: 37,
              lineHeight: '2.5',
            },
          },
        },
      },
    ]);
    expect(input.params.font_size).to.be.equal(37);
    expect(input.params.line_height).to.be.equal('2.5');
  });

  it('Should map minimal heading block', () => {
    const [input] = formBlocksToBody([headingBlock]);
    expect(input.type).to.be.equal('heading');
    expect(input.id).to.be.a('string');
    expect(input.params.content).to.be.equal('');
    expect(input.params.level).to.be.equal(2);
    expect(input.params.align).to.be.equal('left');
    expect(input.params.anchor === null).to.be.equal(true);
    expect(input.params.class_name === null).to.be.equal(true);
  });

  it('Should map full heading block', () => {
    const [input] = formBlocksToBody([
      {
        clientId: 'd9dd2b88-d01f-4a5e-80a4-afaa74de1b00',
        name: 'core/heading',
        isValid: true,
        attributes: {
          content: 'Heading content',
          level: 3,
          textAlign: 'center',
          anchor: 'anchor',
          className: 'class',
          style: {
            color: {
              background: '#321',
              text: '#123',
            },
          },
        },
        innerBlocks: [],
      },
    ]);
    expect(input.type).to.be.equal('heading');
    expect(input.params.content).to.be.equal('Heading content');
    expect(input.params.level).to.be.equal(3);
    expect(input.params.align).to.be.equal('center');
    expect(input.params.text_color).to.be.equal('#123');
    expect(input.params.background_color).to.be.equal('#321');
    expect(input.params.anchor).to.be.equal('anchor');
    expect(input.params.class_name).to.be.equal('class');
  });

  it('Should map font size in heading block', () => {
    const [input] = formBlocksToBody([
      {
        ...headingBlock,
        attributes: {
          fontSize: 'small',
        },
      },
    ]);
    expect(input.params.font_size).to.be.equal(13);
  });

  it('Should map custom font size in heading block', () => {
    const [input] = formBlocksToBody([
      {
        ...headingBlock,
        attributes: {
          fontSize: undefined,
          style: {
            typography: {
              fontSize: 37,
              lineHeight: '2.5',
            },
          },
        },
      },
    ]);
    expect(input.params.font_size).to.be.equal(37);
    expect(input.params.line_height).to.be.equal('2.5');
  });

  it('Should map empty image block', () => {
    const [input] = formBlocksToBody([
      {
        clientId: '895d5bfd-9fef-4b58-83be-7259a7375786',
        name: 'core/image',
        isValid: true,
        attributes: {
          alt: '',
          linkDestination: 'none',
        },
        innerBlocks: [],
      },
    ]);
    expect(input.type).to.be.equal('image');
    expect(input.params.align).to.be.equal(null);
    expect(input.params.url).to.be.equal(null);
    expect(input.params.class_name).to.be.equal(null);
    expect(input.params.alt).to.be.equal(null);
    expect(input.params.title).to.be.equal(null);
    expect(input.params.caption).to.be.equal(null);
    expect(input.params.link_destination).to.be.equal('none');
    expect(input.params.link).to.be.equal(null);
    expect(input.params.id).to.be.equal(null);
    expect(input.params.size_slug).to.be.equal(null);
    expect(input.params.width).to.be.equal(null);
    expect(input.params.height).to.be.equal(null);
  });

  it('Should map image block', () => {
    const [input] = formBlocksToBody([imageBlock]);
    expect(input.type).to.be.equal('image');
    expect(input.params.align).to.be.equal('center');
    expect(input.params.url).to.be.equal('http://example.com/image.jpg');
    expect(input.params.class_name).to.be.equal('my-class');
    expect(input.params.alt).to.be.equal('Alt text');
    expect(input.params.title).to.be.equal('Title');
    expect(input.params.caption).to.be.equal('Caption');
    expect(input.params.link_destination).to.be.equal('none');
    expect(input.params.link).to.be.equal('http://example.com');
    expect(input.params.href).to.be.equal('http://example.com/link');
    expect(input.params.link_class).to.be.equal('link-class');
    expect(input.params.rel).to.be.equal('linkRel');
    expect(input.params.link_target).to.be.equal('_blank');
    expect(input.params.id).to.be.equal(123);
    expect(input.params.size_slug).to.be.equal('medium');
    expect(input.params.width).to.be.equal(100);
    expect(input.params.height).to.be.equal(200);
  });

  it('Should map custom checkbox field', () => {
    const customField = {
      created_at: '2019-12-13T15:22:07+00:00',
      id: 3,
      name: 'Custom Checkbox',
      params: {
        label: 'Check',
        required: '1',
        values: [{ value: 'option 1' }],
      },
      type: 'checkbox',
      updated_at: '2019-12-13T15:22:07+00:00',
    };

    const map = getMapper([customField]);
    const [input] = map([customCheckBox]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('3');
    expect(input.name).to.be.equal('Custom Checkbox');
    expect(input.type).to.be.equal('checkbox');
    expect(input.params.label).to.be.equal('Checkbox');
    expect(isUndefined(input.params.required)).to.be.equal(true);
    expect(isUndefined(input.params.hide_label)).to.be.equal(true);
    expect(input.params.values).to.be.an('Array').that.has.length(1);
    expect(input.params.values[0]).to.have.property('value', 'Check this');
    expect(input.params.values[0]).to.have.property('is_checked', '1');
  });

  it('Should map custom date field', () => {
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
    const [input] = map([customDateBlock]);
    checkBodyInputBasics(input);
    expect(input.id).to.be.equal('6');
    expect(input.name).to.be.equal('Custom Date');
    expect(input.type).to.be.equal('date');
    expect(input.params.label).to.be.equal('Date');
    expect(isUndefined(input.params.required)).to.be.equal(true);
    expect(input.params.date_type).to.be.equal('month_year');
    expect(input.params.date_format).to.be.equal('MM/YYYY');
    expect(input.params.is_default_today).to.be.equal('1');
  });

  it('Should map nested columns blocks', () => {
    const mapped = formBlocksToBody([emailBlock, nestedColumns, submitBlock]);
    const columns = mapped[1];
    expect(mapped.length).to.be.equal(3);
    // First level
    expect(columns.body.length).to.be.equal(2);
    expect(columns.type).to.be.equal('columns');
    expect(columns.params.vertical_alignment).to.be.equal('center');
    expect(columns.params.is_stacked_on_mobile).to.be.equal('0');
    expect(columns.params.padding).to.be.deep.equal({
      top: '1em',
      right: '2em',
      bottom: '3em',
      left: '4em',
    });
    const column1 = columns.body[0];
    const column2 = columns.body[1];
    expect(column1.type).to.be.equal('column');
    expect(column1.params.width).to.be.equal('200px');
    expect(column1.params.padding).to.be.deep.equal({
      top: '10px',
      right: '20px',
      bottom: '30px',
      left: '40px',
    });
    expect(column1.params.vertical_alignment).to.be.equal('center');
    expect(column1.body.length).to.be.equal(2);
    expect(column2.type).to.be.equal('column');
    expect(column2.body.length).to.be.equal(1);
    expect(column2.params.width).to.be.equal('33%');
    const divider = column1.body[1];
    checkBodyInputBasics(divider);
    const submit = column2.body[0];
    checkBodyInputBasics(submit);
    const columns11 = column1.body[0];
    expect(columns11.type).to.be.equal('columns');
    expect(columns11.body.length).to.be.equal(2);
    expect(columns11.params.is_stacked_on_mobile).to.be.equal('1');
    // Second level
    const column11 = columns11.body[0];
    const column12 = columns11.body[1];
    expect(column11.type).to.be.equal('column');
    expect(column11.params.width === null).to.be.equal(true);
    expect(column11.body.length).to.be.equal(1);
    expect(column12.type).to.be.equal('column');
    expect(column12.body.length).to.be.equal(0);
    expect(column12.params.width).to.be.equal('40px');
    const input = column11.body[0];
    checkBodyInputBasics(input);
  });

  it('Should map colors for columns', () => {
    const attributes = {
      textColor: 'black',
      backgroundColor: 'white',
    };

    const [mapped] = formBlocksToBody([{ ...nestedColumns, attributes }]);
    expect(mapped.params.text_color).to.be.equal('#000000');
    expect(mapped.params.background_color).to.be.equal('#ffffff');

    const attributesWithStyles = {
      style: {
        color: {
          text: '#aaaaaa',
          background: '#bbbbbb',
        },
      },
    };
    const [mapped2] = formBlocksToBody([
      { ...nestedColumns, attributes: attributesWithStyles },
    ]);
    expect(mapped2.params.text_color).to.be.equal('#aaaaaa');
    expect(mapped2.params.background_color).to.be.equal('#bbbbbb');
  });

  it('Should map colors for single column', () => {
    const attributes = {
      textColor: 'black',
      backgroundColor: 'white',
    };
    const innerBlockWithColors = {
      ...nestedColumns.innerBlocks[0],
      attributes,
    };
    const innerBlocks = [innerBlockWithColors, nestedColumns.innerBlocks[1]];

    const [mapped] = formBlocksToBody([{ ...nestedColumns, innerBlocks }]);
    expect(mapped.body[0].params.text_color).to.be.equal('#000000');
    expect(mapped.body[0].params.background_color).to.be.equal('#ffffff');

    const attributesWithStyles = {
      style: {
        color: {
          text: '#aaaaaa',
          background: '#bbbbbb',
        },
      },
    };
    const innerBlocksWithStyles = [
      { ...nestedColumns.innerBlocks[0], attributes: attributesWithStyles },
      nestedColumns.innerBlocks[1],
    ];
    const [mapped2] = formBlocksToBody([
      { ...nestedColumns, innerBlocks: innerBlocksWithStyles },
    ]);
    expect(mapped2.body[0].params.text_color).to.be.equal('#aaaaaa');
    expect(mapped2.body[0].params.background_color).to.be.equal('#bbbbbb');
  });

  it('Should map gradient for columns', () => {
    const attributes = {
      gradient: 'black-white',
    };
    const [mapped] = formBlocksToBody([{ ...nestedColumns, attributes }]);
    expect(mapped.params.gradient).to.be.equal(
      'linear-gradient(90deg, rgba(0,0,0,1) 0%, rgba(255,255,255,1) 100%)',
    );

    const attributesWithStyles = {
      style: {
        color: {
          gradient:
            'linear-gradient(95deg, rgba(0,0,0,1) 0%, rgba(255,255,255,1) 100%)',
        },
      },
    };
    const [mapped2] = formBlocksToBody([
      { ...nestedColumns, attributes: attributesWithStyles },
    ]);
    expect(mapped2.params.gradient).to.be.equal(
      'linear-gradient(95deg, rgba(0,0,0,1) 0%, rgba(255,255,255,1) 100%)',
    );
  });

  it('Should map gradient for single column', () => {
    const attributes = {
      gradient: 'black-white',
    };
    const innerBlock = { ...nestedColumns.innerBlocks[0], attributes };
    const innerBlocks = [innerBlock, nestedColumns.innerBlocks[1]];
    const [mapped] = formBlocksToBody([{ ...nestedColumns, innerBlocks }]);
    expect(mapped.body[0].params.gradient).to.be.equal(
      'linear-gradient(90deg, rgba(0,0,0,1) 0%, rgba(255,255,255,1) 100%)',
    );

    const attributesWithStyle = {
      style: {
        color: {
          gradient:
            'linear-gradient(95deg, rgba(0,0,0,1) 0%, rgba(255,255,255,1) 100%)',
        },
      },
    };
    const innerBlockWithStyles = {
      ...nestedColumns.innerBlocks[0],
      attributes: attributesWithStyle,
    };
    const innerBlocksWithStyles = [
      innerBlockWithStyles,
      nestedColumns.innerBlocks[1],
    ];
    const [mapped2] = formBlocksToBody([
      { ...nestedColumns, innerBlocks: innerBlocksWithStyles },
    ]);
    expect(mapped2.body[0].params.gradient).to.be.equal(
      'linear-gradient(95deg, rgba(0,0,0,1) 0%, rgba(255,255,255,1) 100%)',
    );
  });

  it('Should map class names', () => {
    const attributes = {
      className: 'my-class',
    };
    const [mapped] = formBlocksToBody([{ ...nestedColumns, attributes }]);
    expect(mapped.params.class_name).to.be.equal('my-class');

    const columnAttributes = {
      className: 'my-class-2',
    };
    const column = {
      ...nestedColumns.innerBlocks[0],
      attributes: columnAttributes,
    };

    const [mappedColumn] = formBlocksToBody([column]);
    expect(mappedColumn.params.class_name).to.be.equal('my-class-2');

    const emailAttributes = {
      ...emailBlock.attributes,
      className: 'my-class-3',
    };
    const [mappedEmail] = formBlocksToBody([
      { ...emailBlock, attributes: emailAttributes },
    ]);
    expect(mappedEmail.params.class_name).to.be.equal('my-class-3');

    const divider = { ...dividerBlock };
    divider.attributes.className = 'my-class-4';
    const [mappedDivider] = formBlocksToBody([divider]);
    expect(mappedDivider.params.class_name).to.be.equal('my-class-4');

    const htmlAttributes = {
      ...customHtmlBlock.attributes,
      className: 'my-class-5',
    };
    const html = { ...customHtmlBlock, attributes: htmlAttributes };
    html.attributes.className = 'my-class-5';
    const [mappedHtml] = formBlocksToBody([html]);
    expect(mappedHtml.params.class_name).to.be.equal('my-class-5');

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
    const customTextAttributes = {
      ...customTextBlock.attributes,
      className: 'my-class-4',
    };
    const map = getMapper([customField]);
    const [mappedCustomText] = map([
      { ...customTextBlock, attributes: customTextAttributes },
    ]);
    expect(mappedCustomText.params.class_name).to.be.equal('my-class-4');
  });
});
