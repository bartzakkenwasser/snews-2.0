HugeRTE 1.0.15 (MIT licence, see license.txt) - self-hosted copy for sNews.
Only the files that the sNews editor uses are included (core, silver theme, oxide skin, default icons,
the 11 plugins listed in admin.php, and the language packs below).

langs/*.js: translations taken from the TinyMCE 6 language packs (the MIT-licensed 6.x series that HugeRTE is
forked from), with "tinymce.addI18n" renamed to "hugerte.addI18n" as the HugeRTE documentation describes.
Source of the packs: npm package "tinymce-i18n" (langs6 folder).

To upgrade: download a newer "hugerte" package from npm, replace these files, and update the version number
in snews_editor() in admin.php (the CDN fall-back address uses it).
