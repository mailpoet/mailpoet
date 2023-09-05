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
import { Segment, SegmentTemplate } from 'segments/types';
import { getCategoryNameBySlug } from 'segments/dynamic/templates/templates';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from 'segments/dynamic/store';

type TemplateListItemProps = {
  template: SegmentTemplate;
};

export function TemplateListItem({
  template,
}: TemplateListItemProps): JSX.Element {
  const segment: Segment = useSelect(
    (select) => select(storeName).getSegment(),
    [],
  );

  const { updateSegment, createFromTemplate } = useDispatch(storeName);

  function handleSelectTemplate(segmentTemplate: SegmentTemplate): void {
    segment.name = segmentTemplate.name;
    segment.description = segmentTemplate.description;
    segment.filters = segmentTemplate.filters;

    if (segmentTemplate.filtersConnect) {
      segment.filters_connect = segmentTemplate.filtersConnect;
    }

    updateSegment({
      ...segment,
    });
    createFromTemplate();
  }

  return (
    <Card
      className="mailpoet-templates-card"
      onClick={(e): void => {
        e.preventDefault();
        handleSelectTemplate(template);
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
