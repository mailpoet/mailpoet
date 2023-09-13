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
import {
  templates,
  templateCategories,
} from 'segments/dynamic/templates/templates';
import * as ROUTES from 'segments/routes';
import { useSelect } from '@wordpress/data';
import { storeName } from 'segments/dynamic/store';
import { APIErrorsNotice } from 'notices/api_errors_notice';

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
          <Button variant="secondary" href={`#${ROUTES.NEW_DYNAMIC_SEGMENT}`}>
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

      <div className="mailpoet-templates-footer">
        <p>{__('Want to set your own conditions?', 'mailpoet')}</p>
        <Button variant="link" href={`#${ROUTES.NEW_DYNAMIC_SEGMENT}`}>
          {__('Create custom segment', 'mailpoet')}
        </Button>
      </div>
    </div>
  );
}
