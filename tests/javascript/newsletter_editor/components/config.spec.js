define([
    'newsletter_editor/App',
    'newsletter_editor/components/config'
  ], function(EditorApplication, ConfigComponent) {

  describe('Config', function () {
    it('loads and stores configuration', function() {
      ConfigComponent.setConfig({
        testConfig: 'testValue',
      });
      var model = ConfigComponent.getConfig();
      expect(model.get('testConfig')).to.equal('testValue');
    });
  });
});
