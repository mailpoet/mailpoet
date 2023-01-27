import { trim } from 'lodash';
import { State } from '../state_types';
import { CustomFieldStartedAction } from '../actions_types';

export const createCustomFieldStartedFactory =
  (MailPoet) =>
  (state: State, action: CustomFieldStartedAction): State => {
    const notices = state.notices.filter(
      (notice) => notice.id !== 'custom-field',
    );
    const fieldName = trim(action.customField.name);
    const duplicity = state.customFields.find(
      (field) => field.name === fieldName,
    );
    if (duplicity) {
      notices.push({
        id: 'custom-field',
        content: MailPoet.I18n.t('customFieldWithNameExists').replace(
          '[name]',
          fieldName,
        ),
        isDismissible: true,
        status: 'error',
      });
    }
    return {
      ...state,
      isCustomFieldCreating: !duplicity,
      notices,
    };
  };
