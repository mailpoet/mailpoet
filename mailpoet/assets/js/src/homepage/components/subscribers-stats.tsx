import { MailPoet } from 'mailpoet';
import { useSelect } from '@wordpress/data';
import { storeName } from 'homepage/store/store';
import { ContentSection } from './content-section';

export function SubscribersStats(): JSX.Element {
  const { globalChange, listsChange } = useSelect(
    (select) => ({
      globalChange: select(storeName).getGlobalSubscriberStatsChange(),
      listsChange: select(storeName).getListsSubscriberStatsChange(),
    }),
    [],
  );
  return (
    <ContentSection
      heading={MailPoet.I18n.t('subscribersHeading')}
      description={MailPoet.I18n.t('subscribersSectionDescription')}
    >
      <div>
        <div>
          {MailPoet.I18n.t('newSubscribers')}
          <br />
          {globalChange.subscribed}
        </div>
        <div>
          {MailPoet.I18n.t('unsubscribedSubscribers')}
          <br />
          {globalChange.unsubscribed}
        </div>
      </div>
      {listsChange.length ? (
        <table>
          <thead>
            <tr>
              <th>{MailPoet.I18n.t('listName')}</th>
              <th>{MailPoet.I18n.t('subscribedSubscribers')}</th>
              <th>{MailPoet.I18n.t('unsubscribedSubscribers')}</th>
            </tr>
          </thead>
          <tbody>
            {listsChange.map((list) => (
              <tr>
                <td>
                  <a
                    href={`admin.php?page=mailpoet-subscribers#/page[1]/sort_by[created_at]/sort_order[desc]/group[all]/filter[segment=${list.id}]`}
                  >
                    {list.name}
                  </a>
                </td>
                <td>{list.subscribed}</td>
                <td>{list.unsubscribed}</td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : null}
    </ContentSection>
  );
}
