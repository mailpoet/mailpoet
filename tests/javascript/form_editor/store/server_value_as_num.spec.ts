import { expect } from 'chai';

import asNum from '../../../../assets/js/src/form_editor/store/server_value_as_num';

describe.only('Server value as num', () => {
  it('Converts string to number', () => {
    expect(asNum('4')).to.equal(4);
    expect(asNum('0')).to.equal(0);
    expect(asNum('09')).to.equal(9);
    expect(asNum('159')).to.equal(159);
    expect(asNum('-159')).to.equal(-159);
  });

  it('Converts returns undefined', () => {
    expect(asNum('xxx')).to.be.undefined;
    expect(asNum(null)).to.be.undefined;
    expect(asNum(undefined)).to.be.undefined;
  });
});
