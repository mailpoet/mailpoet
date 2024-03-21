# MailPoet Email Editor

This folder contains the code for the MailPoet Email Editor.
We aim to extract the engine as an independent library, so it can be used in other projects.
As we are still in an exploration phase we keep it together with the MailPoet codebase.

## Development

Bot PHP and JS code are divided into `engine` and `integrations` subdirectories.
Anything MailPoet specific is in the integrations/MailPoet folder.

For the core stuff that goes to the engine folder, avoid using other MailPoet-specific services and modules. The code in the Engine folder should work only with WP code or other stuff from the engine.

## Known rendering issues

- In some (not all) Outlook versions the width of columns is not respected. The columns will be rendered with the full width.
