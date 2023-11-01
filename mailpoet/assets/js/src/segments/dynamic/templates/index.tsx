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
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import { TemplateListItem } from 'segments/dynamic/templates/components/template-list-item';
import {
  templates,
  templateCategories,
} from 'segments/dynamic/templates/templates';
import * as ROUTES from 'segments/routes';
import { useSelect } from '@wordpress/data';
import { storeName } from 'segments/dynamic/store';
import { APIErrorsNotice } from 'notices/api-errors-notice';
import { MailPoet } from 'mailpoet';
import { Footer } from '../../../common/templates';

const tabs = [
  {
    name: 'all',
    title: (
      <>
        <span>{__('All', 'mailpoet')}</span>
        <Badge count={templates.length} />
      </>
    ) as any, // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports JSX
  },
];

templateCategories.forEach((category) => {
  const count = templates.filter(
    (template) => template.category === category.slug,
  ).length;

  tabs.push({
    name: category.slug,
    title: (
      <>
        <span>{category.name}</span>
        <Badge count={count} />
      </>
    ) as any, // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports JSX
  });
});

export function SegmentTemplates(): JSX.Element {
  const errors: string[] = useSelect(
    (select) => select(storeName).getErrors(),
    [],
  );

  const trackNewCustomSegment = (): void => {
    MailPoet.trackEvent('Segments > New empty segment');
  };

  return (
    <div className="mailpoet-main-container">
      <HideScreenOptions />
      <TopBarWithBeamer />
      <Flex
        className="mailpoet-heading"
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
          <Button
            variant="secondary"
            href={`#${ROUTES.NEW_DYNAMIC_SEGMENT}`}
            data-automation-id="new-custom-segment"
            onClick={(): void => {
              trackNewCustomSegment();
            }}
          >
            {__('Create custom segment', 'mailpoet')}
          </Button>
        </FlexItem>
      </Flex>

      {errors.length > 0 && (
        <APIErrorsNotice errors={errors.map((error) => ({ message: error }))} />
      )}

      <TabPanel tabs={tabs}>
        {(tab) => (
          <div className="mailpoet-templates-card-grid">
            {templates
              .filter(
                (template) =>
                  tab.name === 'all' || template.category === tab.name,
              )
              .map((template) => (
                <TemplateListItem key={template.name} template={template} />
              ))}
          </div>
        )}
      </TabPanel>

      <Footer>
        <p>{__('Want to set your own conditions?', 'mailpoet')}</p>
        <Button
          variant="link"
          href={`#${ROUTES.NEW_DYNAMIC_SEGMENT}`}
          onClick={() => void trackNewCustomSegment()}
        >
          {__('Create custom segment', 'mailpoet')}
        </Button>
      </Footer>
    </div>
  );
}
