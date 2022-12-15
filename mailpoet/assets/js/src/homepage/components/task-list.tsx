import { MailPoet } from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { storeName } from 'homepage/store/store';

export function TaskList(): JSX.Element {
  const isTaskListHidden = useSelect(
    (select) => select(storeName).getIsTaskListHidden(),
    [],
  );
  const { hideTaskList } = useDispatch(storeName);

  return isTaskListHidden ? null : (
    <>
      <h1>{MailPoet.I18n.t('welcomeToMailPoet')}</h1>
      <h2>{MailPoet.I18n.t('beginByCompletingSetup')}</h2>
      <DropdownMenu
        label={MailPoet.I18n.t('hideList')}
        icon={moreVertical}
        controls={[
          {
            title: MailPoet.I18n.t('hideList'),
            onClick: hideTaskList,
            icon: null,
          },
        ]}
      />
    </>
  );
}
