import { Button, Flex } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import * as ROUTES from 'segments/routes';
import { plusIcon } from 'common/button/icon/plus';
import { SubscribersInPlan } from 'common/subscribers-in-plan';
import { MailPoet } from 'mailpoet';
import { SubscribersCacheMessage } from 'common/subscribers-cache-message';
import { MssAccessNotices } from 'notices/mss-access-notices';

export function ListingHeader(): JSX.Element {
  return (
    <div className="mailpoet-segment-listing-heading">
      <Flex
        direction={['column', 'row'] as any} // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports string[] and this is needed to make the component responsive
      >
        <h1 className="wp-heading-inline">{__('Segments', 'mailpoet')}</h1>
        <Button
          href={`#${ROUTES.DYNAMIC_SEGMENT_TEMPLATES}`}
          icon={plusIcon}
          variant="primary"
          className="mailpoet-add-new-button"
        >
          {__('New segment', 'mailpoet')}
        </Button>
      </Flex>

      <div className="mailpoet-segment-subscriber-count">
        <SubscribersInPlan
          subscribersInPlan={MailPoet.subscribersCount}
          subscribersInPlanLimit={MailPoet.subscribersLimit}
          design="new"
        />
        <SubscribersCacheMessage
          cacheCalculation={window.mailpoet_subscribers_counts_cache_created_at}
          design="new"
        />
      </div>

      <MssAccessNotices />
    </div>
  );
}
