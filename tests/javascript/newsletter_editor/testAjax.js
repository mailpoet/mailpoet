define(['mailpoet', 'ajax'], function(MailPoet) {
  describe('Ajax submodule', function() {
    it('has a version', function() {
      expect(MailPoet.Ajax.version).to.be.a('number');
    });
  });
});
