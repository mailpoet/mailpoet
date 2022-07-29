import { Icon } from '@wordpress/components';
import { ComponentType } from 'react';

export type ColoredIconProps = {
  width: string;
  height: string;
  background: string;
  foreground: string;
  icon: ComponentType;
};

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
        fill: foreground,
      }}
    >
      <Icon {...iconProps} />
    </div>
  );
}
