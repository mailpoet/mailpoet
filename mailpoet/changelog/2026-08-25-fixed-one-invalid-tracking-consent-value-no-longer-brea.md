# Type: Fixed

# Description

One invalid tracking consent value in the database no longer breaks the plugin. A subscriber row holding an out-of-range value, from a hand-edited column, an incomplete migration or a restored backup, used to make MailPoet throw the moment anything saved that subscriber, which took down the whole MailPoet REST API and every signup form on the site. Bad stored values are now harmless to read and to save around, and page-view tracking can no longer stop the rest of the plugin from starting up.
