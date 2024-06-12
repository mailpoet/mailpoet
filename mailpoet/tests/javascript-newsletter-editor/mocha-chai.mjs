import * as chai from 'chai';
import sinonChai from 'sinon-chai';
import chaiJq from 'chai-jq';

chai.use(sinonChai);
chai.use(chaiJq);

global.expect = chai.expect;
