/**
 * Link URLs read from email HTML write "&" as "&amp;" ("&#038;" by esc_url()). Links sent before
 * they were stored with plain ampersands keep that form, so saved link IDs are compared decoded.
 * Only these three forms are decoded, once. Keep in sync with Links::decodeAmpersands() in PHP.
 */
export function decodeAmpersands(url: string): string {
  // Matches "&amp;", "&#038;" or "&#38;"; one pass, so "&amp;amp;" becomes "&amp;"
  return url.replace(/&(?:amp|#038|#38);/g, '&');
}
