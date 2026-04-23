import {
  Button,
  Dropdown,
  Notice,
  Spinner,
  __experimentalVStack as VStack,
  __experimentalHStack as HStack,
  __experimentalHeading as Heading,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import {
  useRef,
  useState,
  useEffect,
  useLayoutEffect,
  useCallback,
} from '@wordpress/element';
import { select } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { closeSmall } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const sparklesSvg =
  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9.5 3l1.5 3.5L14.5 8l-3.5 1.5L9.5 13 8 9.5 4.5 8 8 6.5 9.5 3zm7 7l1 2.5 2.5 1-2.5 1-1 2.5-1-2.5L13 13.5l2.5-1 1-2.5zm-7 7l1 2 2 1-2 1-1 2-1-2-2-1 2-1 1-2z"/></svg>';

type Suggestion = {
  subject: string;
  preheader: string;
};

type Props = {
  onSelect: (suggestion: Suggestion) => void;
};

export function AiSubjectSuggestions({ onSelect }: Props) {
  const isAvailable = Boolean(window.mailpoet_ai_text_generation_available);
  const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);
  const popoverAnchor = useRef<HTMLDivElement>(null);

  useEffect(
    () => () => {
      abortControllerRef.current?.abort();
    },
    [],
  );

  useLayoutEffect(() => {
    const container = popoverAnchor.current;
    if (!container) return;
    const btn = container.querySelector('.mailpoet-ai-suggestions__toggle');
    if (!btn || btn.querySelector('.mailpoet-ai-suggestions__icon')) return;
    const icon = document.createElement('span');
    icon.className = 'mailpoet-ai-suggestions__icon';
    icon.innerHTML = sparklesSvg;
    btn.insertBefore(icon, btn.firstChild);
  });

  const fetchSuggestions = useCallback(() => {
    abortControllerRef.current?.abort();

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setIsLoading(true);
    setError(null);
    setSuggestions([]);

    const postId = select(editorStore).getCurrentPostId();

    apiFetch<{ data: { suggestions: Suggestion[] } }>({
      path: '/mailpoet/v1/email/generate-subject-suggestions',
      method: 'POST',
      data: { post_id: postId },
      signal: controller.signal,
    })
      .then((response) => {
        if (abortControllerRef.current !== controller) return;
        setSuggestions(response.data.suggestions);
      })
      .catch((err: Error & { code?: string }) => {
        if (err.name === 'AbortError') {
          return;
        }
        if (abortControllerRef.current !== controller) return;
        setError(
          err.message ||
            __('Failed to generate suggestions. Please try again.', 'mailpoet'),
        );
      })
      .finally(() => {
        if (abortControllerRef.current === controller) {
          setIsLoading(false);
        }
      });
  }, []);

  if (!isAvailable) {
    return null;
  }

  return (
    <div ref={popoverAnchor}>
      <Dropdown
        popoverProps={{
          anchor: popoverAnchor.current,
          placement: 'left-start',
          offset: 36,
          shift: true,
        }}
        renderToggle={({ isOpen, onToggle }) => (
          <Button
            variant="secondary"
            onClick={() => {
              if (!isOpen) {
                fetchSuggestions();
              }
              onToggle();
            }}
            aria-expanded={isOpen}
            className="mailpoet-ai-suggestions__toggle"
          >
            {__('Suggest with AI', 'mailpoet')}
          </Button>
        )}
        renderContent={({ onClose }) => (
          <div className="mailpoet-ai-suggestions">
            <VStack
              className="block-editor-inspector-popover-header"
              spacing={4}
            >
              <HStack alignment="center">
                <Heading
                  className="block-editor-inspector-popover-header__heading"
                  level={2}
                  size={13}
                >
                  {__('AI suggestions', 'mailpoet')}
                </Heading>
                <Spacer />
                <Button
                  size="small"
                  className="block-editor-inspector-popover-header__action"
                  label={__('Close', 'mailpoet')}
                  icon={closeSmall}
                  onClick={onClose}
                />
              </HStack>
            </VStack>

            {isLoading && (
              <div className="mailpoet-ai-suggestions__loading">
                <Spinner />
                <span>{__('Generating suggestions…', 'mailpoet')}</span>
              </div>
            )}

            {error && (
              <div className="mailpoet-ai-suggestions__error">
                <Notice status="error" isDismissible={false}>
                  {error}
                </Notice>
                <Button variant="secondary" onClick={fetchSuggestions}>
                  {__('Try again', 'mailpoet')}
                </Button>
              </div>
            )}

            {!isLoading &&
              !error &&
              suggestions.map((suggestion) => (
                <button
                  key={suggestion.subject}
                  type="button"
                  className="mailpoet-ai-suggestions__item"
                  onClick={() => {
                    onSelect(suggestion);
                    onClose();
                  }}
                >
                  <strong>{suggestion.subject}</strong>
                  <span>{suggestion.preheader}</span>
                </button>
              ))}
          </div>
        )}
      />
    </div>
  );
}
