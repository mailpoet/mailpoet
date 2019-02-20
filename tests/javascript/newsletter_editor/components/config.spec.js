const expect = global.expect;

import ConfigComponent from 'newsletter_editor/components/config';

describe('Config', function () {
  it('loads and stores configuration', function () {
    var model;
    ConfigComponent.setConfig({
      testConfig: 'testValue'
    });
    model = ConfigComponent.getConfig();
    expect(model.get('testConfig')).to.equal('testValue');
  });
});
