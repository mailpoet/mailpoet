define('test/newsletter_editor/components/config', [
    'newsletter_editor/App',
    'newsletter_editor/components/config'
  ], function(EditorApplication) {

  describe('Config', function () {
    it('loads and stores configuration', function() {
      EditorApplication.module('components.config').setConfig({
        testConfig: 'testValue',
      });
      var model = EditorApplication.module('components.config').getConfig();
      expect(model.get('testConfig')).to.equal('testValue');
    });
  });
});
