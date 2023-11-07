import { __ } from '@wordpress/i18n';
import { Button, SearchControl, TabPanel } from '@wordpress/components';
import { Badge } from '@woocommerce/components';
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import {
  templates,
  templateCategories,
  getCategoryNameBySlug,
} from 'segments/dynamic/templates/templates';
import * as ROUTES from 'segments/routes';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from 'segments/dynamic/store';
import { APIErrorsNotice } from 'notices/api-errors-notice';
import { MailPoet } from 'mailpoet';
import { BackButton, PageHeader } from '../../../common/page-header';
import { Footer, Grid, Item } from '../../../common/templates';

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

  const { createFromTemplate } = useDispatch(storeName);

  const trackNewCustomSegment = (): void => {
    MailPoet.trackEvent('Segments > New empty segment');
  };

  return (
    <div className="mailpoet-main-container">
      <HideScreenOptions />
      <TopBarWithBeamer />
      <PageHeader
        className="mailpoet-templates-header"
        heading={__('Start with a pre-built segment', 'mailpoet')}
        headingPrefix={
          <BackButton
            href="#/"
            label={__('Segments list', 'mailpoet')}
            aria-label={__('Navigate to the segments list page', 'mailpoet')}
          />
        }
      >
        <SearchControl label={__('Search segment templates', 'mailpoet')} />
        <Button
          variant="secondary"
          href={`#${ROUTES.NEW_DYNAMIC_SEGMENT}`}
          data-automation-id="new-custom-segment"
          onClick={() => void trackNewCustomSegment()}
        >
          {__('Create custom segment', 'mailpoet')}
        </Button>
      </PageHeader>

      {errors.length > 0 && (
        <APIErrorsNotice errors={errors.map((error) => ({ message: error }))} />
      )}

      <TabPanel tabs={tabs}>
        {(tab) => (
          <Grid>
            {templates
              .filter(
                (template) =>
                  tab.name === 'all' || template.category === tab.name,
              )
              .map((template) => (
                <Item
                  name={template.name}
                  description={template.description}
                  category={getCategoryNameBySlug(template.category)}
                  isEssential={template.isEssential}
                  onClick={() => void createFromTemplate(template)}
                />
              ))}
          </Grid>
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
