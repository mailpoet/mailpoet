import { EventHandler, MouseEvent } from 'react';
import { Card, CardBody, CardHeader } from '@wordpress/components';
import { Tag } from '@woocommerce/components';
import { ItemBadge } from './item-badge';

type Props = {
  name: string;
  description: string;
  category: string;
  badge?: 'essential' | 'coming-soon' | 'premium';
  onClick?: EventHandler<MouseEvent<HTMLButtonElement>>;
  disabled?: boolean;
};

export function Item({
  name,
  description,
  category,
  badge,
  onClick,
  disabled = false,
}: Props): JSX.Element {
  return (
    <Card
      as="button"
      className="mailpoet-templates-card"
      onClick={onClick}
      disabled={disabled}
      aria-disabled={disabled}
    >
      <CardHeader className="mailpoet-templates-card-header">
        {badge && <ItemBadge type={badge} />}
        <div className="mailpoet-templates-card-header-title">{name}</div>
      </CardHeader>
      <CardBody className="mailpoet-templates-card-body">
        <p>{description}</p>
        <Tag label={category} />
      </CardBody>
    </Card>
  );
}
