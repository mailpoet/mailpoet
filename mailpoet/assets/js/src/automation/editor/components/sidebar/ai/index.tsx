import { useState } from 'react';
import { Button, PanelBody, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { storeName } from '../../../store';
import { PlainBodyTitle } from '../../panel';

export function AiSidebar(): JSX.Element {
  const [state, setState] = useState<'initial' | 'generating' | 'done'>(
    'initial',
  );
  const [prompt, setPrompt] = useState('');
  const { aiGenerate, aiDiscard } = useDispatch(storeName);

  return (
    <>
      <PanelBody title={__('Prompt', 'mailpoet')} initialOpen>
        <TextareaControl
          disabled={state === 'generating' || state === 'done'}
          placeholder="Type in a prompt…"
          value={prompt}
          onChange={setPrompt}
        />
        {['initial', 'generating'].includes(state) ? (
          <Button
            variant="primary"
            disabled={
              state === 'generating' || state === 'done' || prompt.length === 0
            }
            isBusy={state === 'generating'}
            onClick={async () => {
              setState('generating');
              await aiGenerate(prompt);
              setState('done');
            }}
            style={{ display: 'grid', width: '100%' }}
          >
            {state === 'initial' && __('Generate automation', 'mailpoet')}
            {state === 'generating' && __('Generating automation…', 'mailpoet')}
          </Button>
        ) : (
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: '1fr 1fr',
              gap: '8px',
            }}
          >
            <Button
              variant="primary"
              onClick={async () => {}}
              style={{ justifyContent: 'center' }}
            >
              {__('Use', 'mailpoet')}
            </Button>
            <Button
              variant="secondary"
              isDestructive
              onClick={() => {
                setState('initial');
                aiDiscard();
              }}
              style={{ justifyContent: 'center' }}
            >
              {__('Discard', 'mailpoet')}
            </Button>
          </div>
        )}
      </PanelBody>

      <PanelBody opened>
        <PlainBodyTitle
          title={__('Try our some of these prompts:', 'mailpoet')}
        />
        <ul>
          {[
            'Welcome email series with two emails 1 day apart',
            'When a new user subscribes, tag them, and send a welcome email 2 hours later',
            'When a cart is abandoned for 1 hour, send a reminder email 1 day later',
            'When an order is completed, send a notification to jan.jakes@automattic.com',
          ].map((idea) => (
            <li style={{ marginBottom: '10px' }}>
              <Button
                variant="link"
                onClick={() => setPrompt(idea)}
                style={{ textDecoration: 'none', fontStyle: 'italic' }}
              >
                {idea}
              </Button>
            </li>
          ))}
        </ul>
      </PanelBody>
    </>
  );
}
