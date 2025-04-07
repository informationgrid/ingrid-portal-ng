---
title: Anwendungen
custom_title:
  html: PAGES.APPLICATION
  menu: PAGES.APPLICATION
meta:
  title: PAGES.APPLICATION_META_TITLE
  keywords: PAGES.APPLICATION_META_KEYWORDS
  description: PAGES.APPLICATION_META_DESCRIPTION
visible: true
include_twigs:
  - 'partials/global/teaser/teaser-resizer.html.twig'
routes:
  default: /anwendungen
content:
  items: '@self.modular'
  order:
    by: default
---