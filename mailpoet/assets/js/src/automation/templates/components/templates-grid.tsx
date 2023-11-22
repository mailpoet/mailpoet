import { useState } from 'react';
import { AutomationTemplate } from '../config';
import { Grid } from '../../../common/templates';
import { TemplateListItem } from './template-list-item';
import { TemplateDetail } from './template-detail';

type Props = {
  templates: AutomationTemplate[];
};

export function TemplatesGrid({ templates }: Props): JSX.Element {
  const [selectedTemplate, setSelectedTemplate] =
    useState<AutomationTemplate>();

  return (
    <>
      {selectedTemplate && (
        <TemplateDetail
          template={selectedTemplate}
          onRequestClose={() => setSelectedTemplate(undefined)}
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
