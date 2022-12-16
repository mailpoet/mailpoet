import { MailPoet } from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { storeName } from 'homepage/store/store';
import { Task } from './task';

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
    <Task
      key="senderSet"
      title={MailPoet.I18n.t('senderSetTask')}
      link="admin.php?page=mailpoet-settings#/basics"
      order={1}
      status={tasksStatus.senderSet}
    />,
  );
  taskListItems.push(
    <Task
      key="mssConnected"
      title={MailPoet.I18n.t('mssConnectedTask')}
      link="admin.php?page=mailpoet-settings#/premium"
      order={2}
      status={tasksStatus.mssConnected}
    />,
  );
  if (MailPoet.isWoocommerceActive) {
    taskListItems.push(
      <Task
        key="wooSubscribersImported"
        title={MailPoet.I18n.t('wooSubscribersImportedTask')}
        link="admin.php?page=mailpoet-woocommerce-setup"
        order={3}
        status={tasksStatus.wooSubscribersImported}
      />,
    );
  }
  taskListItems.push(
    <Task
      key="subscribersAdded"
      title={MailPoet.I18n.t('subscribersAddedTask')}
      link="admin.php?page=mailpoet-import"
      order={MailPoet.isWoocommerceActive ? 4 : 3}
      status={tasksStatus.subscribersAdded}
    />,
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
      <ul>{taskListItems.map((item) => item)}</ul>
    </>
  );
}
