import { __ } from '@wordpress/i18n';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical, pencil, seen } from '@wordpress/icons';
import { MailPoet } from '../../../../../../../mailpoet';

const getEditorLink = (id: number, wpPostId?: number) => {
  if (wpPostId) {
    return MailPoet.getBlockEmailEditorUrl(wpPostId);
  }
  return MailPoet.getNewsletterEditorUrl(id, 'automation');
};

type ActionsProps = {
  id: number;
  previewUrl: string;
  wpPostId?: number | null;
};
export function Actions({
  id,
  previewUrl,
  wpPostId,
}: ActionsProps): JSX.Element {
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
        window.location.href = getEditorLink(id, wpPostId);
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
