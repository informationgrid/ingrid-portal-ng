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

<style>
    .teaser-data.search {
        padding: 20px 32px;
    }

    .teaser-data a h2 {
        display: inline-flex;
    }
    .teaser-data .teaser-data-img {
        margin: 0 12px 0 0;
        width: 28px;
        float: left;
    }
    .teaser-data .teaser-data-info {
        margin: 0 6px;
    }
    .teaser-data .teaser-data-info:hover {
        text-decoration: none;
    }
    .teaser-data .teaser-data-info span.ic-ic-info {
        font-size: 21px;
    }
    @media print, screen and (min-width: 48em) {
        .teaser-data .teaser-data-img {
            width: 40px;
        }
    }
</style>