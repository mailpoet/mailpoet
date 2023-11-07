import { Icon, Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { lock, starFilled } from '@wordpress/icons';
import { comingSoon } from './coming-soon-icon';

type Props = {
  type: 'essential' | 'coming-soon' | 'premium';
};

const getBadgeData = (
  type: Props['type'],
): { tooltip: string; icon: JSX.Element; color: string } => {
  switch (type) {
    case 'essential':
      return {
        tooltip: __('Essential', 'mailpoet'),
        icon: starFilled,
        color: '#007cba',
      };
    case 'coming-soon':
      return {
        tooltip: __('Coming soon', 'mailpoet'),
        icon: comingSoon,
        color: '#949494',
      };
    case 'premium':
      return {
        tooltip: __('Premium', 'mailpoet'),
        icon: lock,
        color: '#FF8A40',
      };
    default:
      return undefined;
  }
};

export function ItemBadge({ type }: Props): JSX.Element {
  const { tooltip, icon, color } = getBadgeData(type);
  return (
    <Tooltip text={tooltip}>
      <div
        className="mailpoet-templates-badge"
        style={{ backgroundColor: color }}
      >
        <Icon icon={icon} size={18} />
      </div>
    </Tooltip>
  );
}
