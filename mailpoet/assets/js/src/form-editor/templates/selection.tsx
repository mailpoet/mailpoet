import { __, _x } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { Categories } from 'common/categories/categories';
import { Background } from 'common/background/background';
import { Loading } from 'common/loading';
import { TemplateBox } from 'common/template-box/template-box';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import { Notice } from 'notices/notice';
import { TemplateData } from './store/types';
import { storeName } from './store/constants';
import { BackButton, PageHeader } from '../../common/page-header';

export function Selection(): JSX.Element {
  const categories = [
    {
      name: 'popup',
      label: _x(
        'Pop-up',
        'This is a text on a widget that leads to settings for form placement - form type is pop-up, it will be displayed on page in a small modal window',
        'mailpoet',
      ),
    },
    {
      name: 'slide_in',
      label: _x(
        'Slide–in',
        'This is a text on a widget that leads to settings for form placement - form type is slide in',
        'mailpoet',
      ),
    },
    {
      name: 'fixed_bar',
      label: _x(
        'Fixed bar',
        'This is a text on a widget that leads to settings for form placement - form type is fixed bar',
        'mailpoet',
      ),
    },
    {
      name: 'below_posts',
      label: _x(
        'Below pages',
        'This is a text on a widget that leads to settings for form placement',
        'mailpoet',
      ),
    },
    {
      name: 'others',
      label: _x(
        'Others (widget)',
        'Placement of the form using theme widget',
        'mailpoet',
      ),
    },
  ];

  const selectedCategory: string = useSelect(
    (select) => select(storeName).getSelectedCategory(),
    [],
  );

  const templates: TemplateData = useSelect(
    (select) => select(storeName).getTemplates(),
    [],
  );

  const loading: boolean = useSelect(
    (select) => select(storeName).getLoading(),
    [],
  );

  const selectTemplateFailed: boolean = useSelect(
    (select) => select(storeName).getSelectTemplateFailed(),
    [],
  );

  const { selectTemplate, selectCategory } = useDispatch(storeName);
  return (
    <>
      {categories.map((category) =>
        templates[category.name].map(
          (template, index) =>
            index < 4 && (
              <link
                key={`thumbnail_prefetch_${template.id}`}
                rel="preload"
                href={template.thumbnail}
                as="image"
              />
            ),
        ),
      )}
      <TopBarWithBeamer />
      {selectTemplateFailed && (
        <Notice type="error" scroll renderInPlace>
          <p>
            {__(
              'Sorry, there was an error, please try again later.',
              'mailpoet',
            )}
          </p>
        </Notice>
      )}
      <div data-automation-id="template_selection_list">
        <Background color="#fff" />
        <div className="mailpoet-form-templates">
          <PageHeader
            heading={__('Start with a template', 'mailpoet')}
            headingPrefix={
              <BackButton
                href="?page=mailpoet-forms"
                label={__('Back to forms list', 'mailpoet')}
              />
            }
          >
            <Button
              data-automation-id="create_blank_form"
              variant="secondary"
              onClick={(): void => {
                void selectTemplate('initial_form', 'Blank template');
              }}
            >
              {__('Or, start with a blank form', 'mailpoet')}
            </Button>
          </PageHeader>
          <Categories
            categories={categories}
            active={selectedCategory}
            onSelect={selectCategory}
          />
          {templates[selectedCategory].map((template) => (
            <TemplateBox
              key={template.id}
              onSelect={(): void => {
                void selectTemplate(template.id, template.name);
              }}
              label={template.name}
              automationId={`select_template_${template.id}`}
              className="mailpoet-form-template"
            >
              <div className="mailpoet-template-thumbnail">
                <img
                  src={template.thumbnail}
                  alt={template.name}
                  width="480"
                  height="317"
                  loading="lazy"
                />
              </div>
            </TemplateBox>
          ))}
          <div className="mailpoet-form-template-selection-footer">
            <p>
              {__('Can’t find a template that suits your needs?', 'mailpoet')}
            </p>
            <Button
              variant="link"
              onClick={(): void => {
                void selectTemplate('initial_form', 'Blank template');
              }}
            >
              {__('Start with a blank form', 'mailpoet')}
            </Button>
          </div>
        </div>
      </div>
      {loading && <Loading />}
    </>
  );
}
