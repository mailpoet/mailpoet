import { useCallback, useContext, useEffect, useState } from 'react';
import { Badge, Button, Dialog, Stack, Text } from '@wordpress/ui';
import { __ } from '@wordpress/i18n';
import { useNavigate } from 'react-router-dom';
import { GlobalContext, GlobalContextValue } from 'context';
import { MailPoet } from '../../mailpoet';
import { EditorChoice, EditorChoiceOption } from './editor-choice-option';
import {
  BlockEditorIllustration,
  ClassicEditorIllustration,
} from './illustrations';

export function getInitialEditorChoice(): EditorChoice | null {
  const lastChoice = window.mailpoet_last_email_editor_choice;
  return lastChoice === 'classic' || lastChoice === 'block' ? lastChoice : null;
}

type EditorChoiceModalProps = {
  onClose: () => void;
};

export function EditorChoiceModal({ onClose }: EditorChoiceModalProps) {
  const [choice, setChoice] = useState<EditorChoice | null>(
    getInitialEditorChoice,
  );
  const [isCreating, setIsCreating] = useState(false);
  const { notices } = useContext<GlobalContextValue>(GlobalContext);
  const navigate = useNavigate();

  useEffect(() => {
    MailPoet.trackEvent('Emails > Editor choice modal opened');
  }, []);

  const createNewsletter = useCallback(() => {
    if (choice === null) {
      return;
    }
    setIsCreating(true);
    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'user_flags',
      action: 'set',
      data: { last_email_editor_choice: choice },
    });
    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'standard',
        subject: __('Subject', 'mailpoet'),
        new_editor: choice === 'block',
      },
    })
      .done((response) => {
        window.mailpoet_last_email_editor_choice = choice;
        if (choice === 'block') {
          window.location.href = MailPoet.getBlockEmailEditorUrl(
            response.data.wp_post_id as string,
          );
        } else {
          navigate(`/template/${response.data.id as number}`);
        }
      })
      .fail((response) => {
        setIsCreating(false);
        onClose();
        if (response.errors.length > 0) {
          notices.apiError(response, { scroll: true });
        }
      });
  }, [choice, navigate, notices, onClose]);

  return (
    <Dialog.Root
      open
      onOpenChange={(open) => {
        if (!open) {
          MailPoet.trackEvent('Emails > Editor choice modal closed');
          onClose();
        }
      }}
    >
      <Dialog.Popup size="large" className="mailpoet-editor-choice-modal">
        <Dialog.Header>
          <Dialog.Title>
            {__('Choose an email editor', 'mailpoet')}
          </Dialog.Title>
          <Dialog.CloseIcon />
        </Dialog.Header>
        <Stack direction="column" gap="xl">
          <Text variant="body-lg">
            {__(
              'Choose the editor you want to use for this email. You can choose again next time.',
              'mailpoet',
            )}
          </Text>
          <Stack
            direction="row"
            gap="lg"
            role="radiogroup"
            aria-label={__('Email editor', 'mailpoet')}
          >
            <EditorChoiceOption
              value="classic"
              title={__('Classic editor', 'mailpoet')}
              description={__(
                'Choose from 80+ ready-made templates and style each email on its own.',
                'mailpoet',
              )}
              illustration={<ClassicEditorIllustration />}
              isSelected={choice === 'classic'}
              onSelect={setChoice}
            />
            <EditorChoiceOption
              value="block"
              title={
                <>
                  {__('Block editor', 'mailpoet')}{' '}
                  <Badge>{__('Beta', 'mailpoet')}</Badge>
                </>
              }
              description={__(
                'Build with WordPress blocks and shared styles that keep every email consistent.',
                'mailpoet',
              )}
              illustration={<BlockEditorIllustration />}
              isSelected={choice === 'block'}
              onSelect={setChoice}
            />
          </Stack>
        </Stack>
        <Dialog.Footer>
          <Button
            variant="outline"
            disabled={isCreating}
            onClick={() => {
              MailPoet.trackEvent('Emails > Editor choice modal cancelled');
              onClose();
            }}
          >
            {__('Cancel', 'mailpoet')}
          </Button>
          <Button
            variant="solid"
            loading={isCreating}
            disabled={isCreating || choice === null}
            data-automation-id="editor_choice_continue"
            onClick={() => {
              MailPoet.trackEvent('Emails > Type selected', {
                'Email type': 'standard',
              });
              MailPoet.trackEvent(
                'Emails > Editor choice modal continue clicked',
                { editor: choice },
                { send_immediately: true },
                createNewsletter,
              );
            }}
          >
            {__('Continue', 'mailpoet')}
          </Button>
        </Dialog.Footer>
      </Dialog.Popup>
    </Dialog.Root>
  );
}
