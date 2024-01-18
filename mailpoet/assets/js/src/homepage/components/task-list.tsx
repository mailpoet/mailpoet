import { MailPoet } from 'mailpoet';
import { useSelect } from '@wordpress/data';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { stopLinkPropagation } from 'common';
import { storeName } from 'homepage/store/store';
import { Task } from './task';

type Props = {
  onHide: () => void;
};

export function TaskList({ onHide }: Props): JSX.Element {
  const {
    tasksStatus,
    currentTask,
    hasImportedSubscribers,
    canImportWooCommerceSubscribers,
    isNewUserForSenderDomainAuth,
    isFreeMailUser,
    mssActive,
  } = useSelect(
    (select) => ({
      tasksStatus: select(storeName).getTasksStatus(),
      currentTask: select(storeName).getCurrentTask(),
      hasImportedSubscribers: select(storeName).getHasImportedSubscribers(),
      canImportWooCommerceSubscribers:
        select(storeName).getCanImportWooCommerceSubscribers(),
      isNewUserForSenderDomainAuth:
        select(storeName).getIsNewUserForSenderDomainAuth(),
      isFreeMailUser: select(storeName).getIsFreeMailUser(),
      mssActive: select(storeName).getMssActive(),
    }),
    [],
  );

  const taskListItems = [];
  taskListItems.push(
    <Task
      key="senderSet"
      slug="set sender"
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
      slug="connect mss"
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
        slug="import woocommerce subscribers"
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
      slug="add subscribers"
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
      {!tasksStatus.subscribersAdded && (
        <p>
          {MailPoet.I18n.t('noSubscribersQuestion')}{' '}
          <a
            href="admin.php?page=mailpoet-form-editor-template-selection"
            onClick={stopLinkPropagation}
          >
            {MailPoet.I18n.t('setUpForm')}
          </a>
        </p>
      )}
      {tasksStatus.subscribersAdded && !hasImportedSubscribers && (
        <p>
          {MailPoet.I18n.t('haveSubscribersQuestion')}{' '}
          <a href="admin.php?page=mailpoet-import">
            {MailPoet.I18n.t('import')}
          </a>
        </p>
      )}
    </Task>,
  );
  if (isNewUserForSenderDomainAuth && mssActive) {
    let taskLink = 'admin.php?page=mailpoet-settings#/basics';
    if (!isFreeMailUser) {
      taskLink =
        'admin.php?page=mailpoet-settings#/basics/authorizedEmailModal';
    }
    taskListItems.push(
      <Task
        key="senderDomainAuthenticated"
        slug="authenticate sender domain"
        title={MailPoet.I18n.t('senderDomainAuthenticatedTask')}
        titleCompleted={MailPoet.I18n.t('senderDomainAuthenticatedTaskDone')}
        link={taskLink}
        order={canImportWooCommerceSubscribers ? 5 : 4}
        isCompleted={tasksStatus.senderDomainAuthenticated}
        isActive={currentTask === 'senderDomainAuthenticated'}
      >
        {!tasksStatus.senderDomainAuthenticated && (
          <p>{MailPoet.I18n.t('improveDeliveryRates')}</p>
        )}
      </Task>,
    );
  }

  return (
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
              onClick: onHide,
              icon: null,
            },
          ]}
        />
      </div>
      <ul>{taskListItems.map((item) => item)}</ul>
      {!currentTask && (
        <p className="mailpoet-task-list__all-set">
          {MailPoet.I18n.t('youAreSet')}{' '}
          <a
            href="#"
            onClick={(e) => {
              e.preventDefault();
              onHide();
            }}
          >
            {MailPoet.I18n.t('dismissList')}
          </a>
        </p>
      )}
    </>
  );
}
