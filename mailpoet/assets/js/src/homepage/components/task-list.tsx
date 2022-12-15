import { MailPoet } from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { storeName } from 'homepage/store/store';

export function TaskList(): JSX.Element {
  const { isTaskListHidden, tasksStatus } = useSelect(
    (select) => ({
      isTaskListHidden: select(storeName).getIsTaskListHidden(),
      tasksStatus: select(storeName).getTasksStatus(),
    }),
    [],
  );
  const { hideTaskList } = useDispatch(storeName);

  const taskListItems = [];
  taskListItems.push(
    <div key="senderSet">
      {MailPoet.I18n.t('senderSetTask')}{' '}
      {tasksStatus.senderSet ? '[done]' : '[not_done]'}
    </div>,
  );
  taskListItems.push(
    <div key="mssConnected">
      {MailPoet.I18n.t('mssConnectedTask')}{' '}
      {tasksStatus.mssConnected ? '[done]' : '[not_done]'}
    </div>,
  );
  if (MailPoet.isWoocommerceActive) {
    taskListItems.push(
      <div key="wooSubscribersImported">
        {MailPoet.I18n.t('wooSubscribersImportedTask')}{' '}
        {tasksStatus.wooSubscribersImported ? '[done]' : '[not_done]'}
      </div>,
    );
  }
  taskListItems.push(
    <div key="subscribersAdded">
      {MailPoet.I18n.t('subscribersAddedTask')}{' '}
      {tasksStatus.subscribersAdded ? '[done]' : '[not_done]'}
    </div>,
  );

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
      {taskListItems.map((item) => item)}
    </>
  );
}
