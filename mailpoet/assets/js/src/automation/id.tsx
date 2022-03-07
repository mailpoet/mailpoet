const bytes = 8; // = hex string of 16 chars

const byteToHex = (byte: number): string => byte.toString(16).padStart(2, '0');

export const id = (): string => {
  if (!window.crypto || !window.crypto.getRandomValues) {
    throw new Error("Web Crypto API 'crypto.getRandomValues' is not available");
  }

  return Array.from(window.crypto.getRandomValues(new Uint8Array(bytes)))
    .map(byteToHex)
    .join('');
};
