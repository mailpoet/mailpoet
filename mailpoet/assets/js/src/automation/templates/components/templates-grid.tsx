import { useCallback, useEffect, useRef, useState } from 'react';
import { AutomationTemplate } from '../config';
import { Grid } from '../../../common/templates';
import { TemplateListItem } from './template-list-item';
import { TemplateDetail } from './template-detail';
import { premiumValidAndActive } from '../../../common/premium-modal';

const findTemplate = (
  templates: AutomationTemplate[],
  template: AutomationTemplate,
  position: 'previous' | 'next',
): AutomationTemplate | undefined => {
  const index = templates.findIndex(({ slug }) => slug === template.slug);
  const search =
    position === 'previous'
      ? [
          ...templates.slice(0, index).reverse(),
          ...templates.slice(index).reverse(),
        ]
      : [...templates.slice(index + 1), ...templates.slice(0, index + 1)];
  return search.find(
    ({ type }) =>
      type !== 'coming-soon' && (premiumValidAndActive || type !== 'premium'),
  );
};

type Props = {
  templates: AutomationTemplate[];
};

export function TemplatesGrid({ templates }: Props): JSX.Element {
  const ref = useRef<HTMLDivElement>();
  const [selectedTemplate, setSelectedTemplate] =
    useState<AutomationTemplate>();

  const getClickHandler = useCallback(
    (position: 'previous' | 'next') => {
      const template = findTemplate(templates, selectedTemplate, position);
      return template ? () => setSelectedTemplate(template) : undefined;
    },
    [selectedTemplate, templates],
  );

  useEffect(() => {
    // prevent losing focus when previous/next button becomes disabled
    ref.current?.querySelector<HTMLDivElement>('[role=dialog]')?.focus();
  }, [selectedTemplate]);

  useEffect(() => {
    // handle previous/next via keyboard keys
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'ArrowLeft') {
        getClickHandler('previous')?.();
      }
      if (event.key === 'ArrowRight') {
        getClickHandler('next')?.();
      }
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [getClickHandler]);

  return (
    <>
      {selectedTemplate && (
        <TemplateDetail
          ref={ref}
          template={selectedTemplate}
          onRequestClose={() => setSelectedTemplate(undefined)}
          onPreviousClick={getClickHandler('previous')}
          onNextClick={getClickHandler('next')}
        />
      )}
      <Grid>
        {templates.map((template) => (
          <TemplateListItem
            key={template.slug}
            template={template}
            onSelect={() => setSelectedTemplate(template)}
          />
        ))}
      </Grid>
    </>
  );
}
