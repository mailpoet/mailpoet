import { AutomationTemplate } from '../config';
import { Grid } from '../../../common/templates';
import { TemplateListItem } from './template-list-item';

type Props = {
  templates: AutomationTemplate[];
};

export function TemplatesGrid({ templates }: Props): JSX.Element {
  return (
    <Grid>
      {templates.map((template) => (
        <TemplateListItem key={template.slug} template={template} />
      ))}
    </Grid>
  );
}
