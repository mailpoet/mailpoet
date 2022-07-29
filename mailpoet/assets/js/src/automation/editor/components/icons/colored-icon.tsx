import { Icon } from '@wordpress/icons';
import { IconProps } from '@wordpress/icons/build-types/icon';

export type ColoredIconProps = {
  width: string;
  height: string;
  color: string;
} & IconProps;

export function ColoredIcon(props: ColoredIconProps): JSX.Element {
  return (
    <div
      className="mailpoet-automation-colored-icon"
      style={{
        width: props.width,
        height: props.height,
      }}
    >
      <div
        className="mailpoet-automation-colored-icon-background"
        style={{ backgroundColor: props.color }}
      />
      <Icon {...props} />
    </div>
  );
}
