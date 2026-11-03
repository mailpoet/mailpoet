import { isSendClickTooSoon } from '../../../assets/js/src/mailpoet-email-editor-integration/send-panel/send-click-guard';

describe('send panel click guard', () => {
  const openedAt = 10000;

  it('ignores the second click of a double-click that opened the panel', () => {
    expect(isSendClickTooSoon(openedAt, openedAt + 150)).to.equal(true);
  });

  it('ignores a click just before half a second has passed', () => {
    expect(isSendClickTooSoon(openedAt, openedAt + 499)).to.equal(true);
  });

  it('accepts a click once half a second has passed', () => {
    expect(isSendClickTooSoon(openedAt, openedAt + 500)).to.equal(false);
  });
});
