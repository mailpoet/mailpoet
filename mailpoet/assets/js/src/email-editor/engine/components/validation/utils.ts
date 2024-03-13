import { select, dispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

export const addValidationError = (
  errorId: string,
  message: string,
  actions = [],
): void => {
  void dispatch(noticesStore).createNotice('error', message, {
    id: errorId,
    isDismissible: false,
    actions,
    context: 'validation',
  });
};

export const hasValidationError = (
  errorId: string | undefined = undefined,
): boolean => {
  const notices = select(noticesStore).getNotices('validation');

  if (!errorId) {
    return notices?.length > 0;
  }

  return notices.find((notice) => notice.id === errorId) !== undefined;
};

export const removeValidationError = (errorId: string): void => {
  void dispatch(noticesStore).removeNotice(errorId, 'validation');
};
