const CIAB_EVENT_TYPE = 'mailpoet-telemetry-event';

export function sendTelemetryEvent(
  eventSuffix: string,
  properties?: Record<string, string | number | boolean | null>,
): void {
  if (window.parent && window.parent !== window) {
    window.parent.postMessage(
      { type: CIAB_EVENT_TYPE, eventSuffix, properties },
      window.location.origin,
    );
  }
}
