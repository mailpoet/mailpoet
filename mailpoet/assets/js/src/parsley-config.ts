/**
 * Global Parsley options, applied before the library is loaded.
 *
 * By default Parsley walks the whole document on DOM ready and attaches itself to every
 * `[data-parsley-validate]` element it finds. Our public bundle runs on pages we only partly
 * own — post content, theme output and other plugins' markup share that document — so the walk
 * can pick up fields that were never meant for our validator.
 *
 * MailPoet binds its own forms explicitly in `public.tsx`, so nothing of ours needs the walk.
 *
 * This must be assigned before `parsleyjs` is imported anywhere in the bundle: the library reads
 * the flag once, while its module body runs. Keep it as the first import.
 *
 * Assign, do not merge. It is tempting to spread whatever is already on `window` under this name
 * so another plugin's options survive, but on a public page that value is not necessarily config:
 * `window.ParsleyConfig` also resolves to any element in the document carrying
 * `id="ParsleyConfig"`, and merging it would copy page markup into Parsley's options. Our forms
 * should validate by our rules regardless of what else the page defines, so this states the
 * options outright.
 */
window.ParsleyConfig = { autoBind: false };

export {};
