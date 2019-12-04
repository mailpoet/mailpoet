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
    required: 'true',
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

  it('Should map email input to block', () => {
    const [block] = formBodyToBlocks([{ ...emailInput, position: '1' }]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('email');
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

  it('Should map submit button to block', () => {
    const [block] = formBodyToBlocks([{ ...submitInput, position: '1' }]);
    checkBlockBasics(block);
    expect(block.clientId).to.be.equal('submit');
    expect(block.name).to.be.equal('mailpoet-form/submit-button');
    expect(block.attributes.label).to.be.equal('Subscribe!');
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
