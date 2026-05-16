<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sharing;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\WP\Functions as WPFunctions;

class ShareMetadataBuilder {
  private const COPY_BUTTON_ATTRIBUTE = 'data-mailpoet-share-copy';

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function injectMetadata(string $html, NewsletterEntity $newsletter, string $canonicalUrl): string {
    $metadata = $this->buildMetadata($html, $newsletter, $canonicalUrl);
    $position = stripos($html, '</head>');
    if ($position === false) {
      return $metadata . "\n" . $html;
    }

    // Inject before </head> using substr_replace rather than preg_replace so
    // that dollar-digit sequences in the newsletter subject (e.g. "Save $50")
    // are not interpreted as backreferences in the replacement string.
    return substr_replace($html, $metadata . "\n", $position, 0);
  }

  /**
   * Inject the share toolbar right after <body>. Pass $replaceStateUrl when the
   * current URL carries subscriber-specific data (e.g. a tokenised view-in-browser
   * link) — the toolbar's JS will rewrite the address bar to that URL on load so
   * the subscriber's token isn't shared by accident if they copy from the bar
   * or use their browser's share menu.
   */
  public function injectShareToolbar(string $html, NewsletterEntity $newsletter, string $canonicalUrl, string $replaceStateUrl = ''): string {
    return $this->injectAfterBodyOpen($html, $this->buildShareToolbar($newsletter, $canonicalUrl, $replaceStateUrl));
  }

  public function buildMetadata(string $html, NewsletterEntity $newsletter, string $canonicalUrl): string {
    $tags = [
      $this->metaTag('name', 'robots', 'noindex, nofollow'),
    ];

    $title = $newsletter->getCampaignNameOrSubject();
    if ($title === '') {
      return implode("\n", $tags);
    }

    $description = trim($newsletter->getPreheader());
    $image = $this->findFirstContentImage($html);

    $tags[] = $this->metaTag('property', 'og:title', $title);
    $tags[] = $this->metaTag('property', 'og:type', 'website');
    $tags[] = $this->metaTag('property', 'og:url', $canonicalUrl);
    $tags[] = $this->metaTag('name', 'twitter:card', $image ? 'summary_large_image' : 'summary');
    $tags[] = $this->metaTag('name', 'twitter:title', $title);

    if ($description !== '') {
      $tags[] = $this->metaTag('property', 'og:description', $description);
      $tags[] = $this->metaTag('name', 'twitter:description', $description);
    }

    if ($image) {
      $tags[] = $this->metaTag('property', 'og:image', $image['src']);
      $tags[] = $this->metaTag('name', 'twitter:image', $image['src']);
      if ($image['alt'] !== '') {
        $tags[] = $this->metaTag('property', 'og:image:alt', $image['alt']);
      }
    }

    return implode("\n", $tags);
  }

  private function buildShareToolbar(NewsletterEntity $newsletter, string $canonicalUrl, string $replaceStateUrl): string {
    $title = __('Share this email', 'mailpoet');
    $description = __('Copy the public link or share it on your favorite channel.', 'mailpoet');
    $emailTitle = $newsletter->getCampaignNameOrSubject() ?: __('Email', 'mailpoet');
    $copyLabel = __('Copy link', 'mailpoet');
    $shareLabel = _x('Share', 'Web Share button label', 'mailpoet');
    $encodedUrl = rawurlencode($canonicalUrl);
    $encodedTitle = rawurlencode($emailTitle);

    $hostAttrs = ' data-mailpoet-share-host';
    if ($replaceStateUrl !== '') {
      $hostAttrs .= ' data-mailpoet-share-replace-state="' . $this->wp->escAttr($replaceStateUrl) . '"';
    }

    $toolbar = $this->buildShareToolbarStyles()
      . '<div class="mailpoet-share-toolbar" role="region" aria-label="' . $this->wp->escAttr($title) . '">'
      . '<div class="mailpoet-share-toolbar__inner">'
      . '<div class="mailpoet-share-toolbar__copy">'
      . '<strong>' . $this->wp->escHtml($title) . '</strong>'
      . '<span>' . $this->wp->escHtml($description) . '</span>'
      . '</div>'
      . '<div class="mailpoet-share-toolbar__controls">'
      . '<div class="mailpoet-share-toolbar__url-row">'
      . '<input class="mailpoet-share-toolbar__url components-text-control__input" type="url" readonly aria-label="' . $this->wp->escAttr(__('Public share link', 'mailpoet')) . '" value="' . $this->wp->escAttr($canonicalUrl) . '" />'
      . $this->buildShareToolbarCopyButton($canonicalUrl, $copyLabel)
      . '</div>'
      . '<div class="mailpoet-share-toolbar__actions">'
      . '<button type="button" class="mailpoet-share-toolbar__button mailpoet-share-toolbar__web-share components-button is-secondary" aria-label="' . $this->wp->escAttr($shareLabel) . '" data-mailpoet-web-share="' . $this->wp->escAttr($canonicalUrl) . '" data-mailpoet-web-share-title="' . $this->wp->escAttr($emailTitle) . '">'
      . $this->buildShareToolbarActionContent('share', $shareLabel)
      . '</button>'
      . $this->buildShareToolbarLink('facebook', $this->wp->escUrl('https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl), 'Facebook', true)
      . $this->buildShareToolbarLink('x', $this->wp->escUrl('https://twitter.com/intent/tweet?url=' . $encodedUrl . '&text=' . $encodedTitle), 'X', true)
      . $this->buildShareToolbarLink('whatsapp', $this->wp->escUrl('https://api.whatsapp.com/send?text=' . rawurlencode(trim($emailTitle . ' ' . $canonicalUrl))), 'WhatsApp', true)
      . $this->buildShareToolbarLink('email', $this->wp->escUrl('mailto:?subject=' . $encodedTitle . '&body=' . $encodedUrl), __('Email', 'mailpoet'), true)
      . '</div></div></div></div>';

    return '<div' . $hostAttrs . '>' . $toolbar . '</div>' . $this->buildShareToolbarScript();
  }

  private function buildShareToolbarCopyButton(string $canonicalUrl, string $copyLabel): string {
    return '<button type="button" class="mailpoet-share-toolbar__button mailpoet-share-toolbar__copy-button components-button is-secondary" aria-label="' . $this->wp->escAttr($copyLabel) . '" ' . self::COPY_BUTTON_ATTRIBUTE . '="' . $this->wp->escAttr($canonicalUrl) . '" data-mailpoet-share-copied-label="' . $this->wp->escAttr(__('Copied', 'mailpoet')) . '">'
      . $this->buildShareToolbarActionContent('copy', $copyLabel)
      . '</button>';
  }

  private function buildShareToolbarLink(string $iconName, string $url, string $label, bool $opensNewTab): string {
    $isSocialLink = in_array($iconName, ['facebook', 'x', 'whatsapp', 'email'], true);
    $className = $isSocialLink
      ? 'mailpoet-share-toolbar__link mailpoet-share-toolbar__social-button mailpoet-share-toolbar__social-button--' . $this->wp->escAttr($iconName) . ' components-button'
      : 'mailpoet-share-toolbar__link components-button is-secondary';

    return '<a class="' . $className . '" href="' . $url . '" aria-label="' . $this->wp->escAttr($label) . '"'
      . ($opensNewTab ? ' target="_blank" rel="noopener noreferrer"' : '')
      . '>'
      . $this->buildShareToolbarActionContent($iconName, $label)
      . '</a>';
  }

  private function buildShareToolbarActionContent(string $iconName, string $label): string {
    return $this->buildShareToolbarIcon($iconName)
      . '<span data-mailpoet-share-label aria-live="polite">' . $this->wp->escHtml($label) . '</span>';
  }

  private function buildShareToolbarIcon(string $iconName): string {
    $icons = [
      'copy' => '<path d="M16 1H4c-1.1 0-2 .9-2 2v12h2V3h12V1Zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2Zm0 16H8V7h11v14Z" />',
      'share' => '<path d="M18 16.1c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11A2.99 2.99 0 1 0 15 5c0 .24.04.47.09.7L8.04 9.81A3 3 0 1 0 8.04 14.2l7.12 4.18c-.05.2-.07.41-.07.62a2.91 2.91 0 1 0 2.91-2.9Z" />',
      'facebook' => '<path d="M14 8.5V6.75c0-.5.4-.75.85-.75H17V2.5h-3.1C10.83 2.5 9 4.33 9 7.2v1.3H6v3.75h3V22h5v-9.75h3.25l.55-3.75H14Z" />',
      'x' => '<path d="M13.7 10.6 21.4 2h-1.8l-6.7 7.5L7.6 2H1.5l8.1 11.5L1.5 22h1.8l7.1-7.8 5.7 7.8h6.1l-8.5-11.4Zm-2.5 2.8-.8-1.1L3.9 3.4h2.8l5.2 7.2.8 1.1 6.9 9h-2.8l-5.6-7.3Z" />',
      'whatsapp' => '<path d="M12.04 2C6.58 2 2.15 6.43 2.15 11.9c0 1.75.46 3.45 1.33 4.95L2 22l5.29-1.39a9.85 9.85 0 0 0 4.75 1.21h.01c5.46 0 9.9-4.43 9.9-9.89A9.9 9.9 0 0 0 12.04 2Zm5.82 14.2c-.25.7-1.45 1.34-2.02 1.43-.52.08-1.18.12-1.9-.12-.44-.14-1-.33-1.72-.64-3.02-1.3-4.99-4.33-5.14-4.53-.15-.2-1.23-1.64-1.23-3.13 0-1.49.78-2.22 1.06-2.52.28-.3.61-.37.82-.37h.59c.19 0 .44-.07.69.53.25.6.85 2.08.92 2.23.08.15.13.33.03.53-.1.2-.15.33-.3.51-.15.18-.32.4-.45.54-.15.15-.31.31-.13.61.18.3.8 1.31 1.71 2.12 1.18 1.05 2.17 1.37 2.47 1.52.3.15.48.13.66-.08.18-.2.76-.89.97-1.2.2-.3.41-.25.69-.15.28.1 1.78.84 2.08.99.3.15.51.23.58.36.08.13.08.75-.17 1.45Z" />',
      'email' => '<path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z" />',
    ];
    if (!isset($icons[$iconName])) {
      return '';
    }

    return '<span class="mailpoet-share-toolbar__icon mailpoet-share-toolbar__icon--' . $this->wp->escAttr($iconName) . '" aria-hidden="true">'
      . '<svg viewBox="0 0 24 24" focusable="false">' . $icons[$iconName] . '</svg>'
      . '</span>';
  }

  private function buildShareToolbarStyles(): string {
    return '<style>'
      . '.mailpoet-share-toolbar{background:#fff;border-bottom:1px solid #dcdcde;color:#1d2327;font-family:system-ui,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;margin-bottom:20px;position:relative;width:100%}'
      . '.mailpoet-share-toolbar *{box-sizing:border-box}'
      . '.mailpoet-share-toolbar__inner{align-items:center;display:flex;gap:24px;margin:0 auto;max-width:800px;padding:16px 24px;width:100%}'
      . '.mailpoet-share-toolbar__copy{display:flex;flex:0 0 260px;flex-direction:column;gap:4px;min-width:0}'
      . '.mailpoet-share-toolbar__copy strong{font-size:16px;font-weight:600;line-height:1.3}'
      . '.mailpoet-share-toolbar__copy span{color:#50575e;font-size:13px;line-height:1.4;text-wrap:balance}'
      . '.mailpoet-share-toolbar__controls{display:flex;flex:1 1 auto;flex-direction:column;gap:10px;min-width:0}'
      . '.mailpoet-share-toolbar__url-row{align-items:flex-end;display:flex;gap:10px}'
      . '.mailpoet-share-toolbar__url{border:1px solid #8c8f94;border-radius:2px;color:#1d2327;flex:1 1 auto;font-family:monospace;font-size:13px;line-height:20px;min-height:40px;min-width:0;padding:8px}'
      . '.mailpoet-share-toolbar__actions{display:flex;flex-wrap:wrap;gap:10px}'
      . '.mailpoet-share-toolbar__button,.mailpoet-share-toolbar__link{align-items:center;border-radius:2px;display:inline-flex;font-size:13px;font-weight:500;gap:6px;justify-content:center;line-height:20px;min-height:40px;padding:6px 12px;text-decoration:none;white-space:nowrap}'
      . '.mailpoet-share-toolbar__button.components-button.is-secondary,.mailpoet-share-toolbar__link.components-button.is-secondary{background:#fff;border:1px solid #2271b1;color:#2271b1;cursor:pointer}'
      . '.mailpoet-share-toolbar__button.components-button.is-secondary:hover,.mailpoet-share-toolbar__button.components-button.is-secondary:focus,.mailpoet-share-toolbar__link.components-button.is-secondary:hover,.mailpoet-share-toolbar__link.components-button.is-secondary:focus{border-color:#135e96;color:#135e96}'
      . '.mailpoet-share-toolbar__copy-button{flex:0 0 auto}'
      . '.mailpoet-share-toolbar__social-button.components-button{border:1px solid transparent;box-shadow:none;color:#fff;flex:1 1 0}'
      . '.mailpoet-share-toolbar__social-button.components-button:hover,.mailpoet-share-toolbar__social-button.components-button:focus{border-color:transparent;color:#fff;opacity:.85}'
      . '.mailpoet-share-toolbar__social-button--facebook.components-button{background:#1877f2}'
      . '.mailpoet-share-toolbar__social-button--x.components-button{background:#111}'
      . '.mailpoet-share-toolbar__social-button--whatsapp.components-button{background:#25d366}'
      . '.mailpoet-share-toolbar__social-button--email.components-button{background:#3858e9}'
      . '.mailpoet-share-toolbar__icon{display:inline-flex;height:20px;width:20px}'
      . '.mailpoet-share-toolbar__icon svg{display:block;fill:currentColor;height:20px;width:20px}'
      . '.mailpoet-share-toolbar__web-share{display:none}'
      . '@media(max-width:782px){.mailpoet-share-toolbar__inner{align-items:stretch;flex-direction:column;gap:12px;padding:16px}.mailpoet-share-toolbar__copy{flex:auto}.mailpoet-share-toolbar__url-row{align-items:stretch;flex-direction:column}.mailpoet-share-toolbar__button,.mailpoet-share-toolbar__link{width:100%}}'
      . '</style>';
  }

  private function buildShareToolbarScript(): string {
    return '<script>'
      . '(function(){'
      . 'var hosts=document.querySelectorAll("[data-mailpoet-share-host]");'
      // Replace state once if any host asks us to scrub a tokenised URL from the address bar.
      . 'for(var r=0;r<hosts.length;r++){var replace=hosts[r].getAttribute("data-mailpoet-share-replace-state");if(replace&&window.history&&typeof window.history.replaceState==="function"){try{window.history.replaceState(null,document.title,replace);}catch(error){}break;}}'
      . 'function copyUrl(url){'
      . 'if(navigator.clipboard&&window.isSecureContext){return navigator.clipboard.writeText(url);}'
      . 'var input=document.createElement("textarea");'
      . 'input.value=url;input.setAttribute("readonly","");input.style.position="fixed";input.style.left="-9999px";document.body.appendChild(input);input.select();'
      . 'try{document.execCommand("copy");}catch(error){}'
      . 'document.body.removeChild(input);return Promise.resolve();'
      . '}'
      . 'var copyButtons=document.querySelectorAll("[' . self::COPY_BUTTON_ATTRIBUTE . ']");'
      . 'for(var i=0;i<copyButtons.length;i++){if(copyButtons[i].dataset.mailpoetShareBound){continue;}copyButtons[i].dataset.mailpoetShareBound="1";copyButtons[i].addEventListener("click",function(){var button=this;var labelElement=button.querySelector("[data-mailpoet-share-label]");var label=labelElement?labelElement.textContent:button.textContent;copyUrl(button.getAttribute("' . self::COPY_BUTTON_ATTRIBUTE . '")).then(function(){var copiedLabel=button.getAttribute("data-mailpoet-share-copied-label")||label;if(labelElement){labelElement.textContent=copiedLabel;}else{button.textContent=copiedLabel;}window.setTimeout(function(){if(labelElement){labelElement.textContent=label;}else{button.textContent=label;}},2000);});});}'
      . 'var shareButtons=document.querySelectorAll("[data-mailpoet-web-share]");'
      . 'for(var j=0;j<shareButtons.length;j++){if(!navigator.share||shareButtons[j].dataset.mailpoetShareBound){continue;}shareButtons[j].dataset.mailpoetShareBound="1";shareButtons[j].style.display="inline-flex";shareButtons[j].addEventListener("click",function(){navigator.share({title:this.getAttribute("data-mailpoet-web-share-title"),url:this.getAttribute("data-mailpoet-web-share")}).catch(function(){});});}'
      // Promote each host into its own shadow root so email styles can\'t leak in or out.
      // Handlers were bound above on the original nodes; moving them into the shadow root preserves listeners.
      . 'for(var k=0;k<hosts.length;k++){var host=hosts[k];if(host.shadowRoot||typeof host.attachShadow!=="function"){continue;}try{var shadow=host.attachShadow({mode:"open"});while(host.firstChild){shadow.appendChild(host.firstChild);}}catch(error){}}'
      . '})();'
      . '</script>';
  }

  private function injectAfterBodyOpen(string $html, string $markup): string {
    $bodyPosition = stripos($html, '<body');
    if ($bodyPosition === false) {
      return $markup . $html;
    }

    $bodyOpeningTagEnd = strpos($html, '>', $bodyPosition);
    if ($bodyOpeningTagEnd === false) {
      return $markup . $html;
    }

    return substr($html, 0, $bodyOpeningTagEnd + 1) . $markup . substr($html, $bodyOpeningTagEnd + 1);
  }

  /**
   * @return array{src: string, alt: string}|null
   */
  private function findFirstContentImage(string $html): ?array {
    $dom = pQuery::parseStr($html);
    foreach ($dom->query('img') as $image) {
      $src = trim((string)$image->src);
      if (!$this->isUsableImage($src, $image)) {
        continue;
      }
      return [
        'src' => $src,
        'alt' => trim((string)$image->alt),
      ];
    }
    return null;
  }

  private function isUsableImage(string $src, $image): bool {
    if ($src === '' || !preg_match('/^https?:\/\//i', $src)) {
      return false;
    }

    $haystack = strtolower($src . ' ' . (string)$image->alt);
    foreach (['fake-logo', 'your-logo-placeholder', 'social-icons', 'mailpoet-logo', 'powered-by-mailpoet'] as $needle) {
      if (strpos($haystack, $needle) !== false) {
        return false;
      }
    }

    $width = isset($image->width) ? (int)$image->width : 0;
    $height = isset($image->height) ? (int)$image->height : 0;
    if (($width > 0 && $width < 64) || ($height > 0 && $height < 64)) {
      return false;
    }

    return true;
  }

  private function metaTag(string $attribute, string $name, string $content): string {
    return sprintf(
      '<meta %s="%s" content="%s" />',
      $attribute,
      $this->wp->escAttr($name),
      $this->wp->escAttr($content)
    );
  }
}
