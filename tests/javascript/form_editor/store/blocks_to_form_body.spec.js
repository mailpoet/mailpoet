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

const checkBodyInputBasics = (input) => {
  expect(input.id).to.be.a('string');
  expect(parseInt(input.position, 10)).to.be.a('number');
  expect(input.type).to.be.a('string');
  expect(input.type).to.be.not.empty;
  expect(input.params).to.be.a('Object');
};

describe('Form Body To Blocks', () => {
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

  it('Should map multiple blocks at once', () => {
    const unknownBlock = {
      name: 'unknown',
      clientId: '1234',
      attributes: {
        id: 'unknowns',
      },
    };
    const inputs = formBlocksToBody([unknownBlock, submitBlock, emailBlock]);
    inputs.map(checkBodyInputBasics);
    expect(inputs.length).to.be.equal(2);
    expect(inputs[0].id).to.be.equal('submit');
    expect(inputs[0].position).to.be.equal('1');
    expect(inputs[1].id).to.be.equal('email');
    expect(inputs[1].position).to.be.equal('2');
  });
});
