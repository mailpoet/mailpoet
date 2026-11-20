import { useEffect, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { getPresetPreview } from './api';

// Width of the rendered email layout; the iframe is scaled down from it.
const EMAIL_WIDTH = 660;
// Tall enough that the email never scrolls inside the frame; the card clips it.
const IFRAME_HEIGHT = EMAIL_WIDTH * 6;

// Rendered HTML per preset version, so switching layouts or pages does not render again.
const previewCache = new Map<string, string>();

export function loadPreview(
  id: number,
  cacheKey: string,
  signal?: AbortSignal,
): Promise<string> {
  const cached = previewCache.get(cacheKey);
  if (cached !== undefined) {
    return Promise.resolve(cached);
  }
  return getPresetPreview(id, signal).then((html) => {
    previewCache.set(cacheKey, html);
    return html;
  });
}

type Props = {
  id: number;
  cacheKey: string;
  title: string;
};

export function PresetThumbnail({ id, cacheKey, title }: Props) {
  const containerRef = useRef<HTMLDivElement>(null);
  const [html, setHtml] = useState<string | null>(
    () => previewCache.get(cacheKey) ?? null,
  );
  const [hasError, setHasError] = useState(false);
  const [scale, setScale] = useState(0);

  // Render only when the card scrolls into view.
  useEffect(() => {
    const container = containerRef.current;
    if (!container || html !== null) return undefined;
    const controller = new AbortController();
    const observer = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) return;
      observer.disconnect();
      loadPreview(id, cacheKey, controller.signal)
        .then(setHtml)
        .catch((error: Error) => {
          if (error.name !== 'AbortError') setHasError(true);
        });
    });
    observer.observe(container);
    return () => {
      observer.disconnect();
      controller.abort();
    };
  }, [id, cacheKey, html]);

  useEffect(() => {
    const container = containerRef.current;
    if (!container) return undefined;
    const update = () => setScale(container.clientWidth / EMAIL_WIDTH);
    update();
    const observer = new ResizeObserver(update);
    observer.observe(container);
    return () => observer.disconnect();
  }, []);

  return (
    <div className="mailpoet-email-template-thumbnail" ref={containerRef}>
      {html !== null && scale > 0 && (
        <iframe
          title={title}
          sandbox=""
          srcDoc={html}
          scrolling="no"
          tabIndex={-1}
          aria-hidden="true"
          style={{
            width: EMAIL_WIDTH,
            height: IFRAME_HEIGHT,
            transform: `scale(${scale})`,
          }}
        />
      )}
      {hasError && (
        <span className="mailpoet-email-template-thumbnail__error">
          {__('Preview unavailable', 'mailpoet')}
        </span>
      )}
    </div>
  );
}
