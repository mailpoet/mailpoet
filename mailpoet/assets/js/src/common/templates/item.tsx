import { EventHandler, MouseEvent } from 'react';
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

type Props = {
  name: string;
  description: string;
  category: string;
  isEssential?: boolean;
  onClick?: EventHandler<MouseEvent<HTMLDivElement>>;
};

export function Item({
  name,
  description,
  category,
  isEssential = false,
  onClick,
}: Props): JSX.Element {
  return (
    <Card className="mailpoet-templates-card" onClick={onClick}>
      <CardHeader className="mailpoet-templates-card-header">
        {isEssential && (
          <Tooltip text={__('Essential', 'mailpoet')}>
            <div className="mailpoet-templates-badge-essential">
              <Icon icon={starFilled} size={18} />
            </div>
          </Tooltip>
        )}
        <Button variant="link">{name}</Button>
      </CardHeader>
      <CardBody className="mailpoet-templates-card-body">
        <p>{description}</p>
        <Tag label={category} />
      </CardBody>
    </Card>
  );
}
