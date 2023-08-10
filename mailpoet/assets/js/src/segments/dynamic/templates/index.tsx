import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';
import {
  Button,
  Flex,
  FlexItem,
  FlexBlock,
  SearchControl,
  TabPanel,
} from '@wordpress/components';
import { Badge } from '@woocommerce/components';
import { HideScreenOptions } from 'common/hide_screen_options/hide_screen_options';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import { TemplateListItem } from 'segments/dynamic/templates/components/template_list_item';

const tabConfig = [
  {
    name: 'all',
    title: (
      <>
        <span>All</span>
        <Badge count={21} />
      </>
    ) as any, // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports JSX
  },
  {
    name: 'essentials',
    title: (
      <>
        <span>Essentials</span>
        <Badge count={10} />
      </>
    ) as any, // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports JSX
  },
  {
    name: 'engagement',
    title: (
      <>
        <span>Engagement</span>
        <Badge count={9} />
      </>
    ) as any, // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports JSX
  },
  {
    name: 'purchase-history',
    title: (
      <>
        <span>Purchase history</span>
        <Badge count={6} />
      </>
    ) as any, // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports JSX
  },
  {
    name: 'shopping-behavior',
    title: (
      <>
        <span>Shopping behavior</span>
        <Badge count={3} />
      </>
    ) as any, // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports JSX
  },
  {
    name: 'predictive',
    title: (
      <>
        <span>Predictive</span>
        <Badge count={3} />
      </>
    ) as any, // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports JSX
  },
] as const;

const templates = [
  {
    id: 1,
    name: 'Welcome series for new users',
    description:
      'Subscribers who have not engaged with your store or campaigns in the past 30 days.',
    category: 'Disengaged',
    isEssential: true,
  },
  {
    id: 2,
    name: 'Welcome series for new users',
    description:
      'Subscribers who have not engaged with your store or campaigns in the past 30 days.',
    category: 'Disengaged',
    isEssential: true,
  },
  {
    id: 3,
    name: 'Welcome series for new users',
    description:
      'Subscribers who have not engaged with your store or campaigns in the past 30 days.',
    category: 'Disengaged',
    isEssential: true,
  },
  {
    id: 4,
    name: 'Welcome series for new users',
    description:
      'Subscribers who have not engaged with your store or campaigns in the past 30 days.',
    category: 'Disengaged',
    isEssential: true,
  },
  {
    id: 5,
    name: 'Welcome series for new users',
    description:
      'Subscribers who have not engaged with your store or campaigns in the past 30 days.',
    category: 'Disengaged',
    isEssential: true,
  },
  {
    id: 6,
    name: 'Clickers',
    description:
      'Subscribers who have not engaged with your store or campaigns in the past 30 days.',
    category: 'Engagement',
    isEssential: false,
  },
  {
    id: 7,
    name: 'Recent clickers',
    description:
      'Subscribers who have not engaged with your store or campaigns in the past 30 days.',
    category: 'Engagement',
    isEssential: false,
  },
  {
    id: 8,
    name: 'Non-Openers',
    description:
      'Subscribers who have not engaged with your store or campaigns in the past 30 days.',
    category: 'Engagement',
    isEssential: false,
  },
  {
    id: 9,
    name: 'Recent openers',
    description:
      'Subscribers who have not engaged with your store or campaigns in the past 30 days.',
    category: 'Engagement',
    isEssential: false,
  },
] as const;

export function SegmentTemplates(): JSX.Element {
  return (
    <div className="mailpoet-templates-container">
      <HideScreenOptions />
      <TopBarWithBeamer />
      <Flex
        className="mailpoet-templates-heading"
        direction={['column', 'row'] as any} // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports string[] and this is needed to make the component responsive
        gap="16px"
      >
        <FlexBlock>
          <h1 className="wp-heading-inline">
            <Button
              icon={chevronLeft}
              aria-label={__('Navigate to the segments list page', 'mailpoet')}
              href="#/"
              label={__('Segments list', 'mailpoet')}
            />
            {__('Start with a pre-built segment', 'mailpoet')}
          </h1>
        </FlexBlock>

        <FlexItem>
          <SearchControl label={__('Search segment templates', 'mailpoet')} />
        </FlexItem>

        <FlexItem>
          <Button variant="secondary" href="#/new-segment">
            {__('Create custom segment', 'mailpoet')}
          </Button>
        </FlexItem>
      </Flex>

      <TabPanel tabs={tabConfig}>
        {() => (
          <div className="mailpoet-templates-card-grid">
            {templates.map((template) => (
              <TemplateListItem key={template.id} template={template} />
            ))}
          </div>
        )}
      </TabPanel>

      <div className="mailpoet-templates-footer">
        <p>{__('Want to set your own conditions?', 'mailpoet')}</p>
        <Button variant="link" href="#/new-segment">
          {__('Create custom segment', 'mailpoet')}
        </Button>
      </div>
    </div>
  );
}
