import { tinyMceAdd } from 'newsletter-editor/tinymce/mailpoet-shortcodes.js';

const expect = global.expect;
const sinon = global.sinon;

describe('MailPoet shortcodes TinyMCE plugin', function () {
  it('registers the shortcodes button with a tooltip', function () {
    var addButton = sinon.spy();
    var editor = {
      options: { register: sinon.spy(), get: sinon.stub() },
      ui: { registry: { addIcon: sinon.spy(), addButton: addButton } },
    };

    tinyMceAdd(editor);

    expect(addButton.calledOnceWith('mailpoet_shortcodes')).to.equal(true);
    expect(addButton.firstCall.args[1].tooltip).to.equal('Insert shortcode');
  });
});
