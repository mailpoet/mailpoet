import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

export function ActivityCell(): JSX.Element {
  return <Button variant="link">{__('View activity', 'mailpoet')}</Button>;
}
