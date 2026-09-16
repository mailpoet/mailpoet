import { useCallback, useContext, useEffect, useState } from 'react';
import { Badge, Button, Dialog, Stack, Text } from '@wordpress/ui';
import { CheckboxControl } from '@wordpress/components';
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

export function getRememberedEditorChoice(): EditorChoice {
  return window.mailpoet_editor_choice_modal_enabled
    ? 'classic'
    : getInitialEditorChoice() ?? 'classic';
}

type EditorChoiceModalProps = {
  onClose: () => void;
};

export function EditorChoiceModal({ onClose }: EditorChoiceModalProps) {
  const [initialChoice] = useState<EditorChoice | null>(getInitialEditorChoice);
  const [choice, setChoice] = useState<EditorChoice | null>(initialChoice);
  const isRemembered = !window.mailpoet_editor_choice_modal_enabled;
  const [remember, setRemember] = useState(isRemembered);
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
        window.mailpoet_last_email_editor_choice = choice;
        window.mailpoet_editor_choice_modal_enabled = !remember;
        if (choice === 'block') {
          void saveChoice.always(() => {
            window.location.href = MailPoet.getBlockEmailEditorUrl(
              response.data.wp_post_id as string,
            );
          });
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
  }, [choice, remember, isRemembered, navigate, notices, onClose]);

  return (
    <Dialog.Root
      open
      onOpenChange={(open) => {
        if (!open && !isCreating) {
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
              setIsCreating(true);
              MailPoet.trackEvent('Emails > Type selected', {
                'Email type': 'standard',
              });
              MailPoet.trackEvent(
                'Emails > Editor choice modal continue clicked',
                { editor: choice, remember, preselected: initialChoice },
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
