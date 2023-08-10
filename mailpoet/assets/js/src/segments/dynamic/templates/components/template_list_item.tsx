import {
  Button,
  Card,
  CardBody,
  CardHeader,
  Icon,
  Tooltip,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { starFilled } from '@wordpress/icons';
import { Tag } from '@woocommerce/components';

type TemplateListItemProps = {
  template: {
    id: number;
    name: string;
    description: string;
    category: string;
    isEssential: boolean;
  };
};

export function TemplateListItem({
  template,
}: TemplateListItemProps): JSX.Element {
  return (
    <Card className="mailpoet-templates-card">
      <CardHeader className="mailpoet-templates-card-header">
        {template.isEssential && (
          <Tooltip text={__('Essential segment', 'mailpoet')}>
            <div className="mailpoet-templates-badge-essential">
              <Icon icon={starFilled} size={18} />
            </div>
          </Tooltip>
        )}
        <Button variant="link" href="#/">
          {template.name}
        </Button>
      </CardHeader>
      <CardBody className="mailpoet-templates-card-body">
        <p>{template.description}</p>
        <Tag label={template.category} />
      </CardBody>
    </Card>
  );
}
