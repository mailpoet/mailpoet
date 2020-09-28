import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';
import Categories from 'common/categories/categories';
import Background from 'common/background/background';
import { TemplateData } from './store/types';

export default () => {
  const [selectedCategory, setSelectedCategory] = useState('popup');

  const categories = [
    {
      name: 'popup',
      label: MailPoet.I18n.t('popupCategory'),
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
      name: 'slide_in',
      label: MailPoet.I18n.t('slideInCategory'),
    },
    {
      name: 'others',
      label: MailPoet.I18n.t('othersCategory'),
    },
  ];

  const templates: TemplateData = useSelect(
    (select) => select('mailpoet-form-editor-templates').getTemplates(),
    []
  );

  // const selectTemplateFailed: boolean = useSelect(
  //   (select) => select('mailpoet-form-editor-templates').getSelectTemplateFailed(),
  //   []
  // );

  const { selectTemplate } = useDispatch('mailpoet-form-editor-templates');
  return (
    <div className="template-selection" data-automation-id="template_selection_list">
      <Background color="#fff" />
      <div className="mailpoet-templates">
        <Categories
          categories={categories}
          active={selectedCategory}
          onSelect={setSelectedCategory}
        />
        <ol>
          {templates[selectedCategory].map((template, index) => (
            <li key={template.id}>
              <Button
                isLink
                onClick={() => selectTemplate(template.id)}
                data-automation-id={`template_index_${index}`}
              >
                {template.name}
              </Button>
            </li>
          ))}
        </ol>
      </div>

    </div>
  );
};
