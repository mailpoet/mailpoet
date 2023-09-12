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
import { SegmentTemplate } from 'segments/types';
import { getCategoryNameBySlug } from 'segments/dynamic/templates/templates';
import { useDispatch } from '@wordpress/data';
import { storeName } from 'segments/dynamic/store';
import { MailPoet } from 'mailpoet';

type TemplateListItemProps = {
  template: SegmentTemplate;
};

export function TemplateListItem({
  template,
}: TemplateListItemProps): JSX.Element {
  const { createFromTemplate } = useDispatch(storeName);

  const handleSelectingTemplate = (event): void => {
    event.preventDefault();
    createFromTemplate(template);
    MailPoet.trackEvent('Segments > Template selected', {
      'Segment name': template.name,
      'Segment slug': template.slug,
      'Segment category': template.category,
    });
  };

  return (
    <Card
      className="mailpoet-templates-card"
      onClick={(event): void => {
        handleSelectingTemplate(event);
      }}
    >
      <CardHeader className="mailpoet-templates-card-header">
        {template.isEssential && (
          <Tooltip text={__('Essential segment', 'mailpoet')}>
            <div className="mailpoet-templates-badge-essential">
              <Icon icon={starFilled} size={18} />
            </div>
          </Tooltip>
        )}
        <Button variant="link">{template.name}</Button>
      </CardHeader>
      <CardBody className="mailpoet-templates-card-body">
        <p>{template.description}</p>
        <Tag label={getCategoryNameBySlug(template.category)} />
      </CardBody>
    </Card>
  );
}
