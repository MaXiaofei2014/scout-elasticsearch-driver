<?php

namespace SynergyScoutElastic;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Console\Kernel;
use Mockery;
use stdClass;
use SynergyScoutElastic\Builders\SearchBuilder;
use SynergyScoutElastic\Client\ClientInterface;
use SynergyScoutElastic\Stubs\ModelStub;

class ElasticEngineTest extends TestCase
{

    public function testIfTheUpdateMethodBuildCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'id'    => 1,
            'body'  => [
                'id'         => 1,
                'test_field' => 'test text'
            ]
        ];

        $model = $this->mockModel([
            'id'         => 1,
            'test_field' => 'test text'
        ]);

        $engine = $this->getEngine('index', $params);
        $engine->update(Collection::make([$model]));

        $this->addToAssertionCount(1);
    }

    protected function mockModel($fields = [])
    {
        return Mockery::mock(ModelStub::class)
            ->makePartial()
            ->forceFill($fields);
    }

    private function getEngine($method, $params, $builder = null, $options = [])
    {
        $client = $this->prophesize(ClientInterface::class);
        $kernel = $this->prophesize(Kernel::class);

        if ($method == 'search') {
            $client->$method($builder, $options)->shouldbeCalled();
            $client->buildSearchQueryPayloadCollection($builder, $options)->willReturn($params);
        } elseif ($method) {
            $client->$method($params)->shouldbeCalled();
        }
        $engine = new ElasticEngine($kernel->reveal(), $client->reveal(), true);

        return $engine;
    }

    public function testIfTheDeleteMethodBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'id'    => 1
        ];

        $model  = $this->mockModel(['id' => 1]);
        $engine = $this->getEngine('delete', $params);
        $engine->delete(Collection::make([$model]));
        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodBuildsCorrectPayload()
    {
        $params  = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'match' => [
                                '_all' => 'test query'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $builder = new SearchBuilder($this->mockModel(), 'test query');
        $engine  = $this->getEngine('search', $params, $builder);
        $engine->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithSpecifiedLimitBuildsCorrectPayload()
    {

        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'match' => [
                                '_all' => 'test query'
                            ]
                        ]
                    ]
                ],
                'size'  => 10
            ]
        ];

        $builder = (new SearchBuilder($this->mockModel(), 'test query'))->take(10);
        $engine  = $this->getEngine('search', $params, $builder);
        $engine->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSeachMethodWithSpecifieddOrderBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'match' => [
                                '_all' => 'test query'
                            ]
                        ]
                    ]
                ],
                'sort'  => [
                    ['name' => 'asc']
                ]
            ]
        ];

        $builder = (new SearchBuilder($this->mockModel(), 'test query'))
            ->orderBy('name', 'asc');
        $engine  = $this->getEngine('search', $params, $builder);
        $engine->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithSpecifiedWhereClauseBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must'   => [
                            'match' => [
                                '_all' => 'phone'
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'must'     => [
                                    [
                                        'term' => [
                                            'brand' => 'apple'
                                        ]
                                    ],
                                    [
                                        'term' => [
                                            'color' => 'red'
                                        ]
                                    ],
                                    [
                                        'range' => [
                                            'memory' => [
                                                'gte' => 32
                                            ]
                                        ]
                                    ],
                                    [
                                        'range' => [
                                            'battery' => [
                                                'gt' => 1500
                                            ]
                                        ]
                                    ],
                                    [
                                        'range' => [
                                            'weight' => [
                                                'lt' => 200
                                            ]
                                        ]
                                    ],
                                    [
                                        'range' => [
                                            'price' => [
                                                'lte' => 700
                                            ]
                                        ]
                                    ]
                                ],
                                'must_not' => [
                                    [
                                        'term' => [
                                            'used' => 'yes'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $model = $this->mockModel();

        $builder = (new SearchBuilder($model, 'phone'))
            ->where('brand', 'apple')
            ->where('color', '=', 'red')
            ->where('memory', '>=', 32)
            ->where('battery', '>', 1500)
            ->where('weight', '<', 200)
            ->where('price', '<=', 700)
            ->where('used', '<>', 'yes');

        $engine = $this->getEngine('search', $params, $builder);

        $engine->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithSpecifiedWhereInClauseBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must'   => [
                            'match' => [
                                '_all' => 'test query'
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'terms' => [
                                            'id' => [1, 2, 3, 4, 5]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $model   = $this->mockModel();
        $builder = (new SearchBuilder($model, 'test query'))->whereIn('id', [1, 2, 3, 4, 5]);

        $this->getEngine('search', $params, $builder)->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithSpecifiedWherenotinClauseBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must'   => [
                            'match' => [
                                '_all' => 'test query'
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'must_not' => [
                                    [
                                        'terms' => [
                                            'id' => [1, 2, 3, 4, 5]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $model = $this->mockModel();

        $builder = (new SearchBuilder($model, 'test query'))->whereNotIn('id', [1, 2, 3, 4, 5]);

        $this->getEngine('search', $params, $builder)->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithSpecifiedWherebetweenClauseBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must'   => [
                            'match' => [
                                '_all' => 'test query'
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'range' => [
                                            'price' => [
                                                'gte' => 100,
                                                'lte' => 300
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $model = $this->mockModel();

        $builder = (new SearchBuilder($model, 'test query'))->whereBetween('price', [100, 300]);

        $this->getEngine('search', $params, $builder)->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithSpecifiedWherenotbetweenClauseBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must'   => [
                            'match' => [
                                '_all' => 'test query'
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'must_not' => [
                                    [
                                        'range' => [
                                            'price' => [
                                                'gte' => 100,
                                                'lte' => 300
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $model = $this->mockModel();

        $builder = (new SearchBuilder($model, 'test query'))->whereNotBetween('price', [100, 300]);

        $this->getEngine('search', $params, $builder)->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithSpecifiedWhereexistsClauseBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must'   => [
                            'match' => [
                                '_all' => 'test query'
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'exists' => [
                                            'field' => 'sale'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $model = $this->mockModel();

        $builder = (new SearchBuilder($model, 'test query'))->whereExists('sale');

        $this->getEngine('search', $params, $builder)->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithSpecifiedWherenotexistsClauseBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must'   => [
                            'match' => [
                                '_all' => 'test query'
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'must_not' => [
                                    [
                                        'exists' => [
                                            'field' => 'sale'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $model = $this->mockModel();

        $builder = (new SearchBuilder($model, 'test query'))->whereNotExists('sale');

        $this->getEngine('search', $params, $builder)->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithSpecifiedWhereregexpClauseBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must'   => [
                            'match' => [
                                '_all' => 'phone'
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'regexp' => [
                                            'brand' => [
                                                'value' => 'a[a-z]+',
                                                'flags' => 'ALL'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $model = $this->mockModel();

        $builder = (new SearchBuilder($model, 'phone'))->whereRegexp('brand', 'a[a-z]+', 'ALL');

        $this->getEngine('search', $params, $builder)->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithSpecifiedRuleBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'match' => [
                                'name' => 'John'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $model = $this->mockModel();

        $builder = (new SearchBuilder($model, 'John'))->strategy(function ($builder) {
            return [
                'must' => [
                    'match' => [
                        'name' => $builder->query
                    ]
                ]
            ];
        });

        $this->getEngine('search', $params, $builder)->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchMethodWithAnAsteriskBuildsCorrectPayload()
    {
        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'match_all' => new stdClass()
                        ]
                    ]
                ]
            ]
        ];

        $model = $this->mockModel();

        $builder = new SearchBuilder($model, '');

        $this->getEngine('search', $params, $builder)->search($builder);

        $this->addToAssertionCount(1);
    }

    public function testIfTheSearchRawMethodBuildsCorrectPayload()
    {
        $params     = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'match' => [
                                'phone' => 'iphone'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $queryArray = [
            'query' => [
                'bool' => [
                    'must' => [
                        'match' => [
                            'phone' => 'iphone'
                        ]
                    ]
                ]
            ]
        ];
        $model      = $this->mockModel();
        $client     = $this->prophesize(ClientInterface::class);
        $kernel     = $this->prophesize(Kernel::class);

        $client->searchRaw($model, $queryArray)->shouldbeCalled();
        $client->buildTypePayload($model, $queryArray)->willReturn($params);

        $engine = new ElasticEngine($kernel->reveal(), $client->reveal(), true);

        $engine->searchRaw($model, $queryArray);
        $this->addToAssertionCount(1);
    }

    public function testIfThePaginateMethodBuildsCorrectPayload()
    {
        $size  = 8;
        $start = 16;

        $params = [
            'index' => 'test_index',
            'type'  => 'test_table',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'match' => [
                                '_all' => 'test query'
                            ]
                        ]
                    ]
                ],
                'size'  => $size,
                'from'  => $start
            ]
        ];

        $model   = $this->mockModel();
        $builder = new SearchBuilder($model, 'test query');
        $engine  = $this->getEngine('search', $params, $builder, ['limit' => $size, 'page' => 2]);

        $engine->paginate($builder, $size, 2);

        $this->addToAssertionCount(1);
    }

    public function testIfTheMapidsMethodReturnsCorrectIds()
    {
        $results = $this->getElasticSearchResponse();

        $this->assertEquals(
            $this->getEngine('', [])->mapIds($results),
            ['1', '3']
        );
    }

    private function getElasticSearchResponse()
    {
        return [
            'took'      => 2,
            'timed_out' => false,
            '_shards'   => [
                'total'      => 5,
                'successful' => 5,
                'failed'     => 0,
            ],
            'hits'      => [
                'total'     => 2,
                'max_score' => 2.3862944,
                'hits'      => [
                    [
                        '_index'  => 'test_index',
                        '_type'   => 'test_table',
                        '_id'     => '1',
                        '_score'  => 2.3862944,
                        '_source' => [
                            'id'         => 1,
                            'test_field' => 'the first item content',
                        ],
                    ],
                    [
                        '_index'  => 'test_index',
                        '_type'   => 'test_table',
                        '_id'     => '3',
                        '_score'  => 2.3862944,
                        '_source' => [
                            'id'         => 3,
                            'test_field' => 'the second item content'
                        ],
                    ]
                ]
            ]
        ];
    }

    public function testIfTheGettotalcountMethodReturnsCorrectNumberOfResults()
    {
        $results = $this->getElasticSearchResponse();

        $this->assertEquals($this->getEngine('', [])->getTotalCount($results), 2);
    }

    public function testIfTheMapMethodReturnsTheSameResultsFromDatabaseAsInSearchResult()
    {
        $searchResults = $this->getElasticSearchResponse();

        $model = $this->mockModel()
            ->shouldReceive('whereIn')
            ->with('id', [1, 3])
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('get')
            ->andReturn(Collection::make([
                $this->mockModel(['id' => 1]),
                $this->mockModel(['id' => 3])
            ]))
            ->getMock();

        $databaseResult = $this->getEngine('', [])->map($searchResults, $model);

        $this->assertEquals(
            array_pluck($searchResults['hits']['hits'], '_id'),
            $databaseResult->pluck('id')->all()
        );
    }

    public function testIfTheExplainMethodBuildsCorrectPayload()
    {
        $model = $this->mockModel();

        $builder = new SearchBuilder($model, 'test query');

        $this->getEngine('debug', true)->explain();

        $this->addToAssertionCount(1);
    }

    public function testIfTheProfileMethodBuildsCorrectPayload()
    {
        $model = $this->mockModel();

        $builder = new SearchBuilder($model, 'test query');

        $this->getEngine('profile', true)->profile();

        $this->addToAssertionCount(1);
    }
}