export function isInEnum<T>(value: unknown, enumObj: T): value is T[keyof T] {
  return Object.values(enumObj).includes(value as T[keyof T]);
}

export async function copyToClipboard(
  id: string,
  resultCallback: (success: boolean) => void,
  alwaysSelectText = false,
) {
  const element: HTMLTextAreaElement | null = document.querySelector(`#${id}`);
  if (!element) {
    resultCallback(false);
    return;
  }
  if (alwaysSelectText) {
    element.focus();
    element.select();
  }
  if (navigator.clipboard) {
    const text = element.value;
    await navigator.clipboard.writeText(text);
    resultCallback(true);
    return;
  }

  // Fallback if navigator.clipboard does not work.
  element.focus();
  element.select();
  if (document.execCommand('copy')) {
    resultCallback(true);
    return;
  }
  resultCallback(false);
}
