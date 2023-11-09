import { Icon, Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { lock, starFilled } from '@wordpress/icons';
import { comingSoon } from './coming-soon-icon';

type Props = {
  type: 'essential' | 'coming-soon' | 'premium';
};

type BadgeData = {
  tooltip: string;
  icon: JSX.Element;
  foregroundColor: string;
  backgroundColor: string;
  borderColor: string;
};

const getBadgeData = (type: Props['type']): BadgeData | undefined => {
  switch (type) {
    case 'essential':
      return {
        tooltip: __('Essential', 'mailpoet'),
        icon: starFilled,
        foregroundColor: '#fff',
        backgroundColor: '#007cba',
        borderColor: '#007cba',
      };
    case 'coming-soon':
      return {
        tooltip: __('Coming soon', 'mailpoet'),
        icon: comingSoon,
        foregroundColor: '#fff',
        backgroundColor: '#949494',
        borderColor: '#949494',
      };
    case 'premium':
      return {
        tooltip: __('Premium', 'mailpoet'),
        icon: lock,
        foregroundColor: '#BD8600',
        backgroundColor: '#FCF9E8',
        borderColor: '#F5E6AB',
      };
    default:
      return undefined;
  }
};

export function ItemBadge({ type }: Props): JSX.Element {
  const { tooltip, icon, foregroundColor, backgroundColor, borderColor } =
    getBadgeData(type);

  return (
    <Tooltip text={tooltip}>
      <div
        className="mailpoet-templates-badge"
        style={{ backgroundColor, borderColor }}
      >
        <Icon icon={icon} size={18} style={{ fill: foregroundColor }} />
      </div>
    </Tooltip>
  );
}
