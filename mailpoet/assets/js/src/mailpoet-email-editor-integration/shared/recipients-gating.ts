export type RecipientsGatingState = {
  isLoadingSegments: boolean;
  isLoadingRecipientCount: boolean;
  recipientCountFailed: boolean;
  totalRecipientCount: number;
};

/**
 * Whether the send flow should treat the selection as having no recipients.
 *
 * A failed or timed-out exact count must NOT gate sending — only a successful
 * count of 0 means there are genuinely no recipients. While the count (or the
 * segments) is still loading we also don't gate, to avoid a flash of the
 * no-recipient state.
 */
export function hasNoRecipients(state: RecipientsGatingState): boolean {
  return (
    !state.isLoadingSegments &&
    !state.isLoadingRecipientCount &&
    !state.recipientCountFailed &&
    state.totalRecipientCount === 0
  );
}
