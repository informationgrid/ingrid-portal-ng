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
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}}]}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"bool":{"should":[{"term":{"t04_search.searchterm":"inspireidentifiziert"}}]}}]}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
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
        $selected_facets = json_decode('{"partner": "bb"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"query_string":{"query":"+(partner:bb)"}}]}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"match_all":{}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithSelectionOfDynamicTypeMultiple(): void
    {
        $facet_config = json_decode('[
  {
    "id": "special-types",
    "label": "Ergebnistypen",
    "queries": {
      "metadata": {
        "query": {
          "filter": {
            "term": {
              "datatype": "metadata"
            }
          }
        },
        "search": "datatype:metadata"
      },
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
        $selected_facets = json_decode('{"partner": "bb"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"metadata":{"global":{},"aggs":{"filtered":{"filter":{"query_string":{"query":"partner:bb"}},"aggs":{"final":{"filter":{"term":{"datatype":"metadata"}}}}}}},"inspire":{"global":{},"aggs":{"filtered":{"filter":{"query_string":{"query":"partner:bb"}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"match_all":{}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }



    /** @test */
    public function facetWithMultipleSelectionOfSameGroup(): void
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
        $selected_facets = json_decode('{"partner": "bb,he"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"query_string":{"query":"partner:bb OR partner:he"}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"match_all":{}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithMultipleSelectionOfDifferentGroup(): void
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
        $selected_facets = json_decode('{"partner": "bb", "special-types": "inspire"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"query_string":{"query":"partner:bb"}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithMultipleSelectionOfDifferentGroup2(): void
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
    "id": "actuality",
    "queries": {
      "lastMonth": {
        "query": {
          "filter": {
            "range": {
              "modified": {
                "gte": "now-1M"
              }
            }
          }
        }
      }
    }
  }
]', true);
        $selected_facets = json_decode('{"special-types": "inspire"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"query_string":{"query":"partner:bb"}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithMultipleSelectionOfDifferentGroup3(): void
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
    "id": "doc-types",
    "label": "Dokumententyp",
    "query": {
      "terms": {
        "field": "t01_object.obj_class",
        "exclude": "1000"
      }
    },
    "search": "t01_object.obj_class:%d"
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
  },
  {
    "id": "actuality",
    "queries": {
      "lastMonth": {
        "query": {
          "filter": {
            "range": {
              "modified": {
                "gte": "now-1M"
              }
            }
          }
        }
      }
    }
  }
]', true);
        $selected_facets = json_decode('{"special-types": "inspire", "partner": "bb"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"query_string":{"query":"partner:bb"}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithMultipleSelectionOfDifferentGroup4(): void
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
    "id": "doc-types",
    "label": "Dokumententyp",
    "query": {
      "terms": {
        "field": "t01_object.obj_class",
        "exclude": "1000"
      }
    },
    "search": "t01_object.obj_class:%d"
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
  },
  {
    "id": "actuality",
    "queries": {
      "lastMonth": {
        "query": {
          "filter": {
            "range": {
              "modified": {
                "gte": "now-1M"
              }
            }
          }
        }
      }
    }
  }
]', true);
        $selected_facets = json_decode('{"special-types": "inspire", "doc-types": "1"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

//        $aggs = json_encode($result->aggs);
//        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"query_string":{"query":"partner:bb"}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
        $aggs = json_encode($result->aggs->partner);
        $this->assertSame('{"global":{},"aggs":{"filtered":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}', $aggs);
    }
}
