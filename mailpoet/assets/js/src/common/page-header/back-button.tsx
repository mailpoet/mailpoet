import { Button } from '@wordpress/components';
import { chevronLeft } from '@wordpress/icons';

type Props = Button.Props;

export function BackButton(props: Props): JSX.Element {
  return <Button isSmall icon={chevronLeft} {...props} />;
}
