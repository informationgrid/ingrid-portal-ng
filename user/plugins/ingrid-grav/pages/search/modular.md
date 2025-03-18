---
title: Suche
custom_title:
  html: PAGES.SEARCH
  menu: PAGES.SEARCH_MENU_TITLE
meta:
  title: PAGES.SEARCH_META_TITLE
  keywords: PAGES.SEARCH_META_KEYWORDS
  description: PAGES.SEARCH_META_DESCRIPTION
visible: true
routes:
  default: '/freitextsuche'
content:
  items: '@self.modular'
  order:
    by: default
    custom:
      - _search
      - _similar
      - _result
---