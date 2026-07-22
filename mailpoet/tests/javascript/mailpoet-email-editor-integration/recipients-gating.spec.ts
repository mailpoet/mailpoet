import {
  hasNoRecipients,
  RecipientsGatingState,
} from '../../../assets/js/src/mailpoet-email-editor-integration/shared/recipients-gating';

const baseState: RecipientsGatingState = {
  isLoadingSegments: false,
  isLoadingRecipientCount: false,
  recipientCountFailed: false,
  totalRecipientCount: 5,
};

describe('send-panel recipient gating', () => {
  it('does not gate sending when there are recipients', () => {
    expect(hasNoRecipients({ ...baseState, totalRecipientCount: 5 })).to.equal(
      false,
    );
  });

  it('gates sending on a successful count of zero', () => {
    expect(hasNoRecipients({ ...baseState, totalRecipientCount: 0 })).to.equal(
      true,
    );
  });

  it('does not gate sending when the count request failed, even at zero', () => {
    expect(
      hasNoRecipients({
        ...baseState,
        totalRecipientCount: 0,
        recipientCountFailed: true,
      }),
    ).to.equal(false);
  });

  it('does not gate sending while the recipient count is loading', () => {
    expect(
      hasNoRecipients({
        ...baseState,
        totalRecipientCount: 0,
        isLoadingRecipientCount: true,
      }),
    ).to.equal(false);
  });

  it('does not gate sending while segments are loading', () => {
    expect(
      hasNoRecipients({
        ...baseState,
        totalRecipientCount: 0,
        isLoadingSegments: true,
      }),
    ).to.equal(false);
  });
});
