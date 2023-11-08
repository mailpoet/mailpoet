import classNames from 'classnames';
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
  isBusy?: boolean;
};

export function Item({
  name,
  description,
  category,
  badge,
  onClick,
  disabled = false,
  isBusy = false,
}: Props): JSX.Element {
  return (
    <Card
      as="button"
      className={classNames('mailpoet-templates-card', {
        'mailpoet-templates-card-is-busy': isBusy,
      })}
      onClick={onClick}
      disabled={disabled || isBusy}
      aria-disabled={disabled || isBusy}
    >
      <CardHeader className="mailpoet-templates-card-header">
        {badge && <ItemBadge type={badge} />}
        <div className="mailpoet-templates-card-header-title">{name}</div>
      </CardHeader>
      <CardBody className="mailpoet-templates-card-body">
        <div className="mailpoet-templates-card-description">{description}</div>
        <Tag label={category} />
      </CardBody>
    </Card>
  );
}
