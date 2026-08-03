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
 */
window.ParsleyConfig = { autoBind: false };

export {};
