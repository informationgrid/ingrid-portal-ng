---
title: Startseite
custom_title:
  html: PAGES.HOME
visible: true
routes:
  default: '/startseite'
content:
    items: '@self.modular'
    order:
        by: default
        custom:
            - _search
            - _warning
            - _categories
            - _news
            - _inform
---
