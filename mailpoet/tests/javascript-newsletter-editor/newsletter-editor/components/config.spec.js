import { ConfigComponent } from 'newsletter-editor/components/config';

const expect = global.expect;

describe('Config', function () {
  it('loads and stores configuration', function () {
    var model;
    ConfigComponent.setConfig({
      testConfig: 'testValue',
    });
    model = ConfigComponent.getConfig();
    expect(model.get('testConfig')).to.equal('testValue');
  });
});
