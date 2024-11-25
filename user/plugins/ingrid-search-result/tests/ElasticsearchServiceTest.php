<?php declare(strict_types=1);

namespace Grav\Plugin\tests;

use Grav\Plugin\ElasticsearchService;
use PHPUnit\Framework\TestCase;

final class ElasticsearchServiceTest extends TestCase
{
    /** @test */
    public function facetNoSelection(): void
    {
        $facet_config = json_decode('[{
            "id": "partner",
            "label": "Anbieter",
            "query": {
              "terms": {
                "field": "partner"
              }
            },
            "search": "partner:%s"
          }]', true);
        $selected_facets = json_decode('{}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"partner":{"global":{},"aggs":{"filtered":{"filter":{"match_all":{}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithSelectionSameFacet(): void
    {
        $facet_config = json_decode('[{
            "id": "partner",
            "label": "Anbieter",
            "query": {
              "terms": {
                "field": "partner"
              }
            },
            "search": "partner:%s"
          }]', true);
        $selected_facets = json_decode('{"partner": "bund"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"partner":{"global":{},"aggs":{"filtered":{"filter":{"match_all":{}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }


    /** @test */
    public function facetOfSpecificType(): void
    {
        $facet_config = json_decode('[
  {
    "id": "special-types",
    "label": "Ergebnistypen",
    "queries": {
      "inspire": {
        "query": {
          "filter": {
            "term": {
              "t04_search.searchterm": "inspireidentifiziert"
            }
          }
        },
        "search": "t04_search.searchterm:inspireidentifiziert"
      }
    }
  }
]', true);
        $selected_facets = json_decode('{}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"match_all":{}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithSelectionDifferentFacet(): void
    {
        $facet_config = json_decode('[
  {
    "id": "special-types",
    "label": "Ergebnistypen",
    "queries": {
      "inspire": {
        "query": {
          "filter": {
            "term": {
              "t04_search.searchterm": "inspireidentifiziert"
            }
          }
        },
        "search": "t04_search.searchterm:inspireidentifiziert"
      }
    }
  },
  {
    "id": "partner",
    "label": "Anbieter",
    "query": {
      "terms": {
        "field": "partner"
      }
    },
    "search": "partner:%s"
  }
]', true);
        $selected_facets = json_decode('{"special-types": "inspire"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"match_all":{}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithSelectionOfDynamicType(): void
    {
        $facet_config = json_decode('[
  {
    "id": "special-types",
    "label": "Ergebnistypen",
    "queries": {
      "inspire": {
        "query": {
          "filter": {
            "term": {
              "t04_search.searchterm": "inspireidentifiziert"
            }
          }
        },
        "search": "t04_search.searchterm:inspireidentifiziert"
      }
    }
  },
  {
    "id": "partner",
    "label": "Anbieter",
    "query": {
      "terms": {
        "field": "partner"
      }
    },
    "search": "partner:%s"
  }
]', true);
        $selected_facets = json_decode('{"parner": "bb"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"partner":"bb"},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"match_all":{}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }
}
