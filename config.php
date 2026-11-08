;<?php exit; ?>
; The line above stops this file from being shown in a browser if a web server ever serves it as plain text.
; Do not remove it. (It is an ordinary comment for the settings reader.)

[DATABASE]
; database engine (mysql, sqlite)
engine = "mysql"

; Correct DSN with charset
dsn = "mysql:host=localhost;dbname=snews18;charset=utf8mb4"

; Database User
user = "root"

; Database Password
pass = "geheim"

; Database Prefix
prefix = ""

[SECURITY]
; KEY SECURE_ID. Wordt door snews.php geladen; mag niet leeg zijn.
SECURE_ID = "1234"


; Jouw HASH String voor sessiebeveiliging
string_hash = "use_silly_strings"

; Vaste website-adres (optioneel, aanbevolen zodra de site online staat). Voorbeeld: https://www.mijnsite.nl of https://www.mijnsite.nl/map
; Leeg laten = het adres wordt uit de aanvraag afgeleid (prima voor localhost).
; Fixed website address (optional, recommended once the site is online). Leave empty on localhost.
site_url = ""


[OPTIONS]
; page_list volgorde (home, archive, contact, sitemap, pages)
page_list = "home,archive,contact,sitemap,pages"

; Pagina's die je wilt verbergen in het menu (kommagescheiden seftitles)
ignore_pages = ""

; Categorieën die je wilt verbergen in het menu
ignore_cats = ""

; Divider character (HTML-entity veilig tussen dubbele quotes)
divider = "&middot;"

; Gebruikt in artikel paginering links
paginator = "p_"

; Gebruikt in commentaar paginering links
comment_pages = "c_"


[INFO_TAGS]
; Infoline template
infoline = '<p class="date">,readmore,comments,date,edit,</p>'

; Comments template
comments = '<p class="meta">,name, $on ,date,edit,</p>,<p class="comment">,comment,</p>'
