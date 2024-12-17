---
title: Suche
custom_title:
  html: PAGES.SEARCH
  menu: PAGES.SEARCH_MENU_TITLE
visible: true
routes:
  default: '/freitextsuche'
hasImageSwiper: true
hasLeaflet: true
content:
    items: '@self.modular'
    order:
        by: default
        custom:
            - _search
            - _result
---
