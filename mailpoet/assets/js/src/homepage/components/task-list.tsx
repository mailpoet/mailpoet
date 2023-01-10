import { MailPoet } from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { storeName } from 'homepage/store/store';
import { Task } from './task';

export function TaskList(): JSX.Element {
  const {
    isTaskListHidden,
    tasksStatus,
    currentTask,
    hasImportedSubscribers,
    canImportWooCommerceSubscribers,
  } = useSelect(
    (select) => ({
      isTaskListHidden: select(storeName).getIsTaskListHidden(),
      tasksStatus: select(storeName).getTasksStatus(),
      currentTask: select(storeName).getCurrentTask(),
      hasImportedSubscribers: select(storeName).getHasImportedSubscribers(),
      canImportWooCommerceSubscribers:
        select(storeName).getCanImportWooCommerceSubscribers(),
    }),
    [],
  );
  const { hideTaskList } = useDispatch(storeName);

  const taskListItems = [];
  taskListItems.push(
    <Task
      key="senderSet"
      title={MailPoet.I18n.t('senderSetTask')}
      titleCompleted={MailPoet.I18n.t('senderSetTaskDone')}
      link="admin.php?page=mailpoet-settings#/basics"
      order={1}
      isCompleted={tasksStatus.senderSet}
      isActive={currentTask === 'senderSet'}
    />,
  );
  taskListItems.push(
    <Task
      key="mssConnected"
      title={MailPoet.I18n.t('mssConnectedTask')}
      titleCompleted={MailPoet.I18n.t('mssConnectedTaskDone')}
      link="admin.php?page=mailpoet-settings#/premium"
      order={2}
      isCompleted={tasksStatus.mssConnected}
      isActive={currentTask === 'mssConnected'}
    />,
  );
  if (canImportWooCommerceSubscribers) {
    taskListItems.push(
      <Task
        key="wooSubscribersImported"
        title={MailPoet.I18n.t('wooSubscribersImportedTask')}
        titleCompleted={MailPoet.I18n.t('wooSubscribersImportedTaskDone')}
        link="admin.php?page=mailpoet-woocommerce-setup"
        order={3}
        isCompleted={tasksStatus.wooSubscribersImported}
        isActive={currentTask === 'wooSubscribersImported'}
      />,
    );
  }
  taskListItems.push(
    <Task
      key="subscribersAdded"
      title={MailPoet.I18n.t('subscribersAddedTask')}
      titleCompleted={
        hasImportedSubscribers
          ? MailPoet.I18n.t('subscribersAddedTaskDoneByImport')
          : MailPoet.I18n.t('subscribersAddedTaskDoneByForm')
      }
      link="admin.php?page=mailpoet-import"
      order={canImportWooCommerceSubscribers ? 4 : 3}
      isCompleted={tasksStatus.subscribersAdded}
      isActive={currentTask === 'subscribersAdded'}
    >
      {!tasksStatus.subscribersAdded ? (
        <p>
          {MailPoet.I18n.t('noSubscribersQuestion')}{' '}
          <a href="admin.php?page=mailpoet-form-editor-template-selection">
            {MailPoet.I18n.t('setUpForm')}
          </a>
        </p>
      ) : null}
      {tasksStatus.subscribersAdded && !hasImportedSubscribers ? (
        <p>
          {MailPoet.I18n.t('haveSubscribersQuestion')}{' '}
          <a href="admin.php?page=mailpoet-import">
            {MailPoet.I18n.t('import')}
          </a>
        </p>
      ) : null}
    </Task>,
  );

  return isTaskListHidden ? null : (
    <>
      <div className="mailpoet-task-list__heading">
        <h1>{MailPoet.I18n.t('welcomeToMailPoet')}</h1>
        <p>{MailPoet.I18n.t('beginByCompletingSetup')}</p>
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
      </div>
      <ul>{taskListItems.map((item) => item)}</ul>
      {!currentTask ? (
        <p className="mailpoet-task-list__all-set">
          {MailPoet.I18n.t('youAreSet')}{' '}
          <a
            href="#"
            onClick={(e) => {
              e.preventDefault();
              hideTaskList();
            }}
          >
            {MailPoet.I18n.t('dismissList')}
          </a>
        </p>
      ) : null}
    </>
  );
}
