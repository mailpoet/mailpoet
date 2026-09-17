import { useEffect, useState } from 'react';
import { Badge, Button, Dialog, Stack, Text } from '@wordpress/ui';
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { MailPoet } from '../../mailpoet';
import { EditorChoice, EditorChoiceOption } from './editor-choice-option';
import {
  BlockEditorIllustration,
  ClassicEditorIllustration,
} from './illustrations';

export type { EditorChoice } from './editor-choice-option';

export type EditorChoiceModalProps = {
  context: 'newsletter' | 'automation';
  lastChoice: EditorChoice | null;
  isRemembered: boolean;
  onClose: () => void;
  // creates the email and resolves with the function that opens its editor
  onContinue: (choice: EditorChoice) => Promise<() => void>;
  onChoiceSaved?: (choice: EditorChoice, remember: boolean) => void;
};

export function EditorChoiceModal({
  context,
  lastChoice,
  isRemembered,
  onClose,
  onContinue,
  onChoiceSaved,
}: EditorChoiceModalProps) {
  const [choice, setChoice] = useState<EditorChoice | null>(lastChoice);
  const [remember, setRemember] = useState(isRemembered);
  const [isCreating, setIsCreating] = useState(false);

  useEffect(() => {
    MailPoet.trackEvent('Emails > Editor choice modal opened', { context });
  }, [context]);

  const saveChoiceAndContinue = async () => {
    if (choice === null) {
      return;
    }
    let openEditor: () => void;
    try {
      openEditor = await onContinue(choice);
    } catch {
      setIsCreating(false);
      onClose();
      return;
    }
    const saveChoice = MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'user_flags',
      action: 'set',
      data: {
        last_email_editor_choice: choice,
        // an untouched checkbox leaves the site-wide rollout setting in control
        ...(remember !== isRemembered && {
          remember_email_editor_choice: remember ? 1 : 0,
        }),
      },
    });
    void saveChoice
      .done(() => onChoiceSaved?.(choice, remember))
      .always(() => {
        MailPoet.trackEvent(
          'Emails > Email editor opened',
          { context, editor: choice, via: 'modal' },
          { send_immediately: true },
          openEditor,
        );
      });
  };

  return (
    <Dialog.Root
      open
      onOpenChange={(open) => {
        if (!open && !isCreating) {
          MailPoet.trackEvent('Emails > Editor choice modal closed', {
            context,
          });
          onClose();
        }
      }}
    >
      <Dialog.Popup size="large" className="mailpoet-editor-choice-modal">
        <Dialog.Header>
          <Dialog.Title>
            {__('Choose an email editor', 'mailpoet')}
          </Dialog.Title>
          <Dialog.CloseIcon disabled={isCreating} />
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
            wrap="wrap"
            role="group"
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
          <CheckboxControl
            __nextHasNoMarginBottom
            label={__('Remember my choice', 'mailpoet')}
            checked={remember}
            onChange={setRemember}
            data-automation-id="editor_choice_remember"
          />
        </Stack>
        <Dialog.Footer>
          <Button
            variant="outline"
            disabled={isCreating}
            onClick={() => {
              MailPoet.trackEvent('Emails > Editor choice modal cancelled', {
                context,
              });
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
              setIsCreating(true);
              MailPoet.trackEvent(
                'Emails > Editor choice modal continue clicked',
                { context, editor: choice, remember, preselected: lastChoice },
                { send_immediately: true },
                () => void saveChoiceAndContinue(),
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
