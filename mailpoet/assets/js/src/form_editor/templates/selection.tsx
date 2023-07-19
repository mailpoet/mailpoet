import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { Categories } from 'common/categories/categories';
import { Background } from 'common/background/background';
import { Loading } from 'common/loading';
import { TemplateBox } from 'common/template_box/template_box';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import { Notice } from 'notices/notice';
import { TemplateData } from './store/types';
import { storeName } from './store/constants';

export function Selection(): JSX.Element {
  const categories = [
    {
      name: 'popup',
      label: MailPoet.I18n.t('popupCategory'),
    },
    {
      name: 'slide_in',
      label: MailPoet.I18n.t('slideInCategory'),
    },
    {
      name: 'fixed_bar',
      label: MailPoet.I18n.t('fixedBarCategory'),
    },
    {
      name: 'below_posts',
      label: MailPoet.I18n.t('belowPagesCategory'),
    },
    {
      name: 'others',
      label: MailPoet.I18n.t('othersCategory'),
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
          <p>{MailPoet.I18n.t('createFormError')}</p>
        </Notice>
      )}
      <div data-automation-id="template_selection_list">
        <Background color="#fff" />
        <div className="mailpoet-form-templates">
          <div className="mailpoet-form-template-selection-header">
            <h1 className="wp-heading-inline">
              {__('Start with a template', 'mailpoet')}
            </h1>
            <Button
              data-automation-id="create_blank_form"
              variant="secondary"
              onClick={(): void => {
                void selectTemplate('initial_form', 'Blank template');
              }}
            >
              {__('Or, start with a blank form', 'mailpoet')}
            </Button>
          </div>
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
        </div>
      </div>
      {loading && <Loading />}
    </>
  );
}
