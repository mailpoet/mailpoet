import { Icon } from '@wordpress/icons';
import { IconProps } from '@wordpress/icons/build-types/icon';

export type ColoredIconProps = {
  width: string;
  height: string;
  background: string;
  foreground: string;
} & IconProps;

export function ColoredIcon({
  foreground,
  background,
  ...iconProps
}: ColoredIconProps): JSX.Element {
  return (
    <div
      className="mailpoet-automation-colored-icon"
      style={{
        width: iconProps.width,
        height: iconProps.height,
        backgroundColor: background,
      }}
    >
      <Icon color={foreground} {...iconProps} />
    </div>
  );
}
