var chai = require('chai');
var sinon = require('sinon');
var sinonChai = require('sinon-chai');
var chaiJq = require('chai-jq');

chai.use(sinonChai);
chai.use(chaiJq);

global.expect = chai.expect;
global.sinon = sinon;

if (!global.document || !global.window) {
    var jsdom = require('jsdom').jsdom;

    global.document = jsdom('<html><head><script></script></head><body></body></html>', {}, {
        FetchExternalResources: ['script'],
        ProcessExternalResources: ['script'],
        MutationEvents: '2.0',
        QuerySelector: false
    });

    global.window = document.parentWindow;
    global.navigator = global.window.navigator;

    global.window.Node.prototype.contains = function (node) {
        return this.compareDocumentPosition(node) & 16;
    };
}
