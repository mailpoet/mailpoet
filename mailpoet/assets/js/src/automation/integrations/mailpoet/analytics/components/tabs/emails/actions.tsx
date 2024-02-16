import { __ } from '@wordpress/i18n';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical, pencil, seen } from '@wordpress/icons';

type ActionsProps = {
  id: number;
  previewUrl: string;
};
export function Actions({ id, previewUrl }: ActionsProps): JSX.Element {
  const controls = [
    {
      title: __('Preview email', 'mailpoet'),
      icon: seen,
      onClick: () => {
        window.location.href = previewUrl;
      },
    },
    {
      title: __('Edit email', 'mailpoet'),
      icon: pencil,
      onClick: () => {
        window.location.href = `?page=mailpoet-newsletter-editor&id=${id}&context=automation`;
      },
    },
  ];
  return (
    <div className="mailpoet-analytics-email-actions">
      <p>
        <a href={`admin.php?page=mailpoet-newsletters#/stats/${id}`}>
          {__('Statistics', 'mailpoet')}
        </a>
      </p>

      <DropdownMenu
        label={__('More', 'mailpoet')}
        icon={moreVertical}
        controls={controls}
        popoverProps={{ placement: 'bottom-start' }}
      />
    </div>
  );
}
