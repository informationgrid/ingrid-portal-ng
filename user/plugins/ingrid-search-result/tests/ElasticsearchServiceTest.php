<?php declare(strict_types=1);

namespace Grav\Plugin\tests;

use Grav\Plugin\ElasticsearchService;
use PHPUnit\Framework\TestCase;

final class ElasticsearchServiceTest extends TestCase
{
    /** @test */
    public function facetNoSelection(): void
    {
        $facet_config = $this->getFacetConfig(Category::PARTNER);
        $selected_facets = json_decode('{}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"partner":{"global":{},"aggs":{"filtered":{"filter":{"match_all":{}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithSelectionSameFacet(): void
    {
        $facet_config = $this->getFacetConfig(Category::PARTNER);
        $selected_facets = json_decode('{"partner": "bund"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"partner":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"match_all":{}}]}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }


    /** @test */
    public function facetOfSpecificType(): void
    {
        $facet_config = $this->getFacetConfig(Category::INSPIRE);
        $selected_facets = json_decode('{}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"match_all":{}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithSelectionDifferentFacet(): void
    {
        $facet_config = $this->getFacetConfig(Category::INSPIRE, Category::PARTNER);
        $selected_facets = json_decode('{"special-types": "inspire"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"match_all":{}}]}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"bool":{"must":[{"bool":{"should":[{"term":{"t04_search.searchterm":"inspireidentifiziert"}}]}}]}},{"match_all":{}}]}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithSelectionOfDynamicType(): void
    {
        $facet_config = $this->getFacetConfig(Category::INSPIRE, Category::PARTNER);
        $selected_facets = json_decode('{"partner": "bb"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"query_string":{"query":"+(partner:bb)"}}]}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"match_all":{}}]}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithSelectionOfDynamicTypeMultiple(): void
    {
        $facet_config = $this->getFacetConfig(Category::INSPIRE_AND_METADATA, Category::PARTNER);
        $selected_facets = json_decode('{"partner": "bb"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"metadata":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"query_string":{"query":"+(partner:bb)"}}]}},"aggs":{"final":{"filter":{"term":{"datatype":"metadata"}}}}}}},"inspire":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"query_string":{"query":"+(partner:bb)"}}]}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"match_all":{}}]}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }


    /** @test */
    public function facetWithMultipleSelectionOfSameGroup(): void
    {
        $facet_config = $this->getFacetConfig(Category::INSPIRE, Category::PARTNER);
        $selected_facets = json_decode('{"partner": "bb,he"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"query_string":{"query":"+(partner:bb OR partner:he)"}}]}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"match_all":{}}]}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithMultipleSelectionOfDifferentGroup(): void
    {
        $facet_config = $this->getFacetConfig(Category::INSPIRE, Category::PARTNER);
        $selected_facets = json_decode('{"partner": "bb", "special-types": "inspire"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"query_string":{"query":"+(partner:bb)"}}]}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"bool":{"must":[{"bool":{"should":[{"term":{"t04_search.searchterm":"inspireidentifiziert"}}]}}]}},{"match_all":{}}]}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}}', $aggs);
    }

    /** @test */
    public function facetWithMultipleSelectionOfDifferentGroupPart2(): void
    {
        $facet_config = $this->getFacetConfig(Category::INSPIRE, Category::DOCTYPE, Category::PARTNER, Category::ACTUALITY);
        $selected_facets = json_decode('{"special-types": "inspire", "doc-types": "1"}', true);
        $result = json_decode(ElasticsearchService::convertToQuery("", $facet_config, 0, 10, $selected_facets));

        $partner = json_encode($result->aggs->partner);
        $this->assertSame('{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"bool":{"must":[{"bool":{"should":[{"term":{"t04_search.searchterm":"inspireidentifiziert"}}]}}]}},{"query_string":{"query":"+(t01_object.obj_class:1)"}}]}},"aggs":{"final":{"terms":{"field":"partner"}}}}}}', $partner);

        $aggs = json_encode($result->aggs);
        $this->assertSame('{"inspire":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"match_all":{}},{"query_string":{"query":"+(t01_object.obj_class:1)"}}]}},"aggs":{"final":{"filter":{"term":{"t04_search.searchterm":"inspireidentifiziert"}}}}}}},"doc-types":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"bool":{"must":[{"bool":{"should":[{"term":{"t04_search.searchterm":"inspireidentifiziert"}}]}}]}},{"match_all":{}}]}},"aggs":{"final":{"terms":{"field":"t01_object.obj_class","exclude":"1000"}}}}}},"partner":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"bool":{"must":[{"bool":{"should":[{"term":{"t04_search.searchterm":"inspireidentifiziert"}}]}}]}},{"query_string":{"query":"+(t01_object.obj_class:1)"}}]}},"aggs":{"final":{"terms":{"field":"partner"}}}}}},"lastMonth":{"global":{},"aggs":{"filtered":{"filter":{"bool":{"must":[{"bool":{"must":[{"bool":{"should":[{"term":{"t04_search.searchterm":"inspireidentifiziert"}}]}}]}},{"query_string":{"query":"+(t01_object.obj_class:1)"}}]}},"aggs":{"final":{"filter":{"range":{"modified":{"gte":"now-1M"}}}}}}}}}', $aggs);
    }

    private function getFacetConfig(Category ...$categories): array
    {
        $result = array();
        foreach ($categories as $category) {
            switch ($category) {
                case Category::PARTNER:
                    $result[] = '
                    {
                        "id": "partner",
                        "label": "Anbieter",
                        "query": {
                          "terms": {
                            "field": "partner"
                          }
                        },
                        "search": "partner:%s"
                      }';
                    break;
                case Category::INSPIRE:
                    $result[] = '
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
                      }';
                    break;
                case Category::INSPIRE_AND_METADATA:
                    $result[] = '
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
                      }';
                    break;
                case Category::ACTUALITY:
                    $result[] = '
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
                      }';
                    break;
                case Category::DOCTYPE:
                    $result[] = '
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
                      }';
                    break;
            }
        }
        return json_decode('[' . join(',', $result) . ']', true);
    }

}

enum Category: string
{
    case PARTNER = 'partner';
    case INSPIRE = 'inspire';
    case ACTUALITY = 'actuality';
    case INSPIRE_AND_METADATA = 'inspire_metadata';
    case DOCTYPE = 'doctype';
}
