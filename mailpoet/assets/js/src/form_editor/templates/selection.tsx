import { MailPoet } from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { Categories } from 'common/categories/categories';
import { Background } from 'common/background/background';
import { Loading } from 'common/loading';
import { TemplateBox } from 'common/template_box/template_box';
import { Heading } from 'common/typography/heading/heading';
import { Button } from 'common';
import { Notice } from 'notices/notice';
import { TemplateData } from './store/types';

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
    (select) => select('mailpoet-form-editor-templates').getSelectedCategory(),
    [],
  );

  const templates: TemplateData = useSelect(
    (select) => select('mailpoet-form-editor-templates').getTemplates(),
    [],
  );

  const loading: boolean = useSelect(
    (select) => select('mailpoet-form-editor-templates').getLoading(),
    [],
  );

  const selectTemplateFailed: boolean = useSelect(
    (select) =>
      select('mailpoet-form-editor-templates').getSelectTemplateFailed(),
    [],
  );

  const { selectTemplate, selectCategory } = useDispatch(
    'mailpoet-form-editor-templates',
  );
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
      <div className="mailpoet-template-selection-header">
        <Heading level={4}>{MailPoet.I18n.t('selectTemplate')}</Heading>
        <Button
          automationId="create_blank_form"
          onClick={(): void => {
            void selectTemplate('initial_form', 'Blank template');
          }}
        >
          {MailPoet.I18n.t('createBlankTemplate')}
        </Button>
      </div>
      {selectTemplateFailed && (
        <Notice type="error" scroll renderInPlace>
          <p>{MailPoet.I18n.t('createFormError')}</p>
        </Notice>
      )}
      <div data-automation-id="template_selection_list">
        <Background color="#fff" />
        <div className="mailpoet-templates">
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
