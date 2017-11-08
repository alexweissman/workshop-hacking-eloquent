<?php
/**
 * Hacking Laravel: Custom Relationships in Eloquent
 *
 * @link      https://github.com/alexweissman/phpworld2017
 * @see       https://world.phparch.com/sessions/hacking-laravel-custom-relationships-in-eloquent/
 * @license   MIT
 */
namespace App\Tests;

use Exception;

use App\Database\Models\Model;
use App\Log\ArrayHandler;
use App\Log\MixedFormatter;

use Illuminate\Container\Container;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Events\Dispatcher;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use UserFrosting\Support\Repository\Loader\YamlFileLoader;
use UserFrosting\Support\Repository\Repository;

/**
 * Class DatabaseTests.
 *
 * @author Alex Weissman (https://alexanderweissman.com)
 */
class DatabaseTests extends TestCase
{
    protected $schemaName = 'default';

    public static $arrayHandler = null;

    public static function setUpBeforeClass()
    {
        $loader = new YamlFileLoader(__DIR__ . '/../config/database.yaml');
        $config = new Repository($loader->load());

        $capsule = new DB;

        $capsule->addConnection($config->all());

        $queryEventDispatcher = new Dispatcher(new Container);

        $capsule->setEventDispatcher($queryEventDispatcher);

        // Make this Capsule instance available globally via static methods
        $capsule->setAsGlobal();

        // Setup the Eloquent ORM
        $capsule->bootEloquent();

        // Set up a query logger
        $logger = new Logger('query');

        $formatter = new MixedFormatter(null, null, true);

        static::$arrayHandler = new ArrayHandler();
        static::$arrayHandler->setFormatter($formatter);

        $logger->pushHandler(static::$arrayHandler);

        if (PHP_SAPI == 'cli') {
            $logFile = __DIR__ . '/../log/queries.log';
            $handler = new StreamHandler($logFile);
            $handler->setFormatter($formatter);

            $logger->pushHandler($handler);
        }

        $capsule->connection()->enableQueryLog();

        // Register listener to log performed queries
        $queryEventDispatcher->listen(QueryExecuted::class, function ($query) use ($logger) {
            $logger->debug("Query executed on database [{$query->connectionName}]:", [
                'query'    => $query->sql,
                'bindings' => $query->bindings,
                'time'     => $query->time . ' ms'
            ]);
        });
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function setUp()
    {
        $this->createSchema();

        $this->generateWorkers();
        $this->generateLocations();
        $this->generateJobs();
        $this->generateAssignments();
    }

    protected function createSchema()
    {
        $this->schema($this->schemaName)->create('workers', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });

        $this->schema($this->schemaName)->create('jobs', function ($table) {
            $table->increments('id');
            $table->string('label');
        });

        $this->schema($this->schemaName)->create('locations', function($table) {
            $table->increments('id');
            $table->string('name');
        });

        // Workers have multiple jobs... (m:m)
        $this->schema($this->schemaName)->create('job_workers', function ($table) {
            $table->integer('worker_id')->unsigned();
            $table->integer('job_id')->unsigned();
        });

        // Workers are assigned specific jobs at specific locations... (m:m:m)
        $this->schema($this->schemaName)->create('assignments', function($table) {
            $table->integer('worker_id')->unsigned();
            $table->integer('location_id')->unsigned();
            $table->integer('job_id')->unsigned();
            $table->string('title');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema($this->schemaName)->drop('workers');
        $this->schema($this->schemaName)->drop('jobs');
        $this->schema($this->schemaName)->drop('locations');
        $this->schema($this->schemaName)->drop('job_workers');
        $this->schema($this->schemaName)->drop('assignments');

        Relation::morphMap([], false);
    }

    /**
     * Basic sanity-checking test, to ensure the framework is functioning properly.
     * Should not require any further work to pass.
     */
    public function testBelongsToMany()
    {
        $worker = EloquentTestWorker::first();

        $worker->jobs()->attach([1,2]);

        // Test retrieval of pivots as well
        $this->assertArrayEqual([
            [
                'id' => 1,
                'label' => 'forager',
                'pivot' => [
                    'worker_id' => 1,
                    'job_id' => 1
                ]
            ],
            [
                'id' => 2,
                'label' => 'soldier',
                'pivot' => [
                    'worker_id' => 1,
                    'job_id' => 2
                ]
            ]
        ], $worker->jobs->toArray());
        echo $this->getTaskSuccessMessage(__FUNCTION__);
    }

    /**
     * Test basic relationship on a single model.  See task 1.
     */
    public function testBelongsToTernary()
    {
        $worker = EloquentTestWorker::first();

        $expectedAssignments = [
            [
                'id' => 2,
                'label' => 'soldier',
                'pivot' => [
                    'worker_id' => 1,
                    'job_id' => 2
                ]
            ],
            [
                'id' => 3,
                'label' => 'attendant',
                'pivot' => [
                    'worker_id' => 1,
                    'job_id' => 3
                ]
            ]
        ];

        $assignments = $worker->assignments;
        $this->assertArrayEqual($expectedAssignments, $assignments->toArray(), $this->getTaskFailureMessage(1));
        echo $this->getTaskSuccessMessage(__FUNCTION__);
    }

    /**
     * Test eager loading on a collection of parent models.  See task 2.
     */
    public function testBelongsToTernaryEagerLoad()
    {
        $expectedAssignments = [
            [
                'id' => 2,
                'label' => 'soldier',
                'pivot' => [
                    'worker_id' => 1,
                    'job_id' => 2
                ]
            ],
            [
                'id' => 3,
                'label' => 'attendant',
                'pivot' => [
                    'worker_id' => 1,
                    'job_id' => 3
                ]
            ]
        ];

        $workers = EloquentTestWorker::with('assignments')->get();
        $this->assertArrayEqual($expectedAssignments, $workers->toArray()[0]['assignments'], $this->getTaskFailureMessage(2));
        echo $this->getTaskSuccessMessage(__FUNCTION__);
    }

    /**
     * Test loading of the tertiary relationship on a single model.  See task 3.
     * @dataProvider assignmentsProvider
     */
    public function testBelongsToTernaryWithTertiary($expectedAssignments)
    {
        $worker = EloquentTestWorker::first();

        $assignments = $worker
            ->assignments()
            ->withTertiary(EloquentTestLocation::class, null, 'location_id')
            ->get();

        $this->assertArrayEqual($expectedAssignments, $assignments->toArray(), $this->getTaskFailureMessage(3));
        echo $this->getTaskSuccessMessage(__FUNCTION__);
    }

    /**
     * @dataProvider assignmentsWithTitleProvider
     */
    public function testBelongsToTernaryWithTertiaryAndPivots($expectedAssignments)
    {
        $worker = EloquentTestWorker::first();

        $assignments = $worker
            ->assignments()
            ->withTertiary(EloquentTestLocation::class, null, 'location_id')
            ->withPivot('title')
            ->get();

        $this->assertArrayEqual($expectedAssignments, $assignments->toArray(), $this->getTaskFailureMessage(3));
        echo $this->getTaskSuccessMessage(__FUNCTION__);
    }

    /**
     * @dataProvider assignmentsProvider
     */
    public function testBelongsToTernaryEagerLoadWithTertiary($expectedAssignments)
    {
        $workers = EloquentTestWorker::with(['assignments' => function ($relation) {
            return $relation
                ->withTertiary(EloquentTestLocation::class, null, 'location_id');
        }])->get();

        $this->assertArrayEqual($expectedAssignments, $workers->toArray()[0]['assignments'], $this->getTaskFailureMessage(4));
        echo $this->getTaskSuccessMessage(__FUNCTION__);
    }

    /**
     * @dataProvider assignmentsWithTitleProvider
     */
    public function testBelongsToTernaryEagerLoadWithTertiaryAndPivots($expectedAssignments)
    {
        $workers = EloquentTestWorker::with(['assignments' => function ($relation) {
            return $relation
                ->withTertiary(EloquentTestLocation::class, null, 'location_id')
                ->withPivot('title');
        }])->get();

        $this->assertArrayEqual($expectedAssignments, $workers->toArray()[0]['assignments'], $this->getTaskFailureMessage(4));
        echo $this->getTaskSuccessMessage(__FUNCTION__);
    }

    public function assignmentsProvider()
    {
        return [
            [
                [
                    [
                        'id' => 2,
                        'label' => 'soldier',
                        'pivot' => [
                            'worker_id' => 1,
                            'job_id' => 2
                        ],
                        'locations' => [
                            [
                                'id' => 1,
                                'name' => 'Hatchery',
                                'pivot' => [
                                    'location_id' => 1,
                                    'job_id' => 2
                                ]
                            ],
                            [
                                'id' => 2,
                                'name' => 'Brood Chamber',
                                'pivot' => [
                                    'location_id' => 2,
                                    'job_id' => 2
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 3,
                        'label' => 'attendant',
                        'pivot' => [
                            'worker_id' => 1,
                            'job_id' => 3
                        ],
                        'locations' => [
                            [
                                'id' => 2,
                                'name' => 'Brood Chamber',
                                'pivot' => [
                                    'location_id' => 2,
                                    'job_id' => 3
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function assignmentsWithTitleProvider()
    {
        return [
            [
                [
                    [
                        'id' => 2,
                        'label' => 'soldier',
                        'pivot' => [
                            'worker_id' => 1,
                            'job_id' => 2
                        ],
                        'locations' => [
                            [
                                'id' => 1,
                                'name' => 'Hatchery',
                                'pivot' => [
                                    'title' => 'Grunt',
                                    'location_id' => 1,
                                    'job_id' => 2
                                ]
                            ],
                            [
                                'id' => 2,
                                'name' => 'Brood Chamber',
                                'pivot' => [
                                    'title' => 'Guard',
                                    'location_id' => 2,
                                    'job_id' => 2
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 3,
                        'label' => 'attendant',
                        'pivot' => [
                            'worker_id' => 1,
                            'job_id' => 3
                        ],
                        'locations' => [
                            [
                                'id' => 2,
                                'name' => 'Brood Chamber',
                                'pivot' => [
                                    'title' => 'Feeder',
                                    'location_id' => 2,
                                    'job_id' => 3
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Helpers...
     */

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Model::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }

    /**
     * Generate some sample assignments.  A assignment is a unique triplet of job, location, and worker.
     */
    protected function generateAssignments()
    {
        /**
         * Sample data

        | worker_id | job_id | location_id |
        |-----------|--------|-------------|
        | 1         | 2      | 1           |
        | 1         | 2      | 2           |
        | 1         | 3      | 2           |
        | 2         | 3      | 2           |
        */

        return [
            EloquentTestAssignment::create([
                'job_id' => 2,
                'location_id' => 1,
                'worker_id' => 1,
                'title' => 'Grunt'
            ]),
            EloquentTestAssignment::create([
                'job_id' => 2,
                'location_id' => 2,
                'worker_id' => 1,
                'title' => 'Guard'
            ]),
            EloquentTestAssignment::create([
                'job_id' => 3,
                'location_id' => 2,
                'worker_id' => 1,
                'title' => 'Feeder'
            ]),
            EloquentTestAssignment::create([
                'job_id' => 3,
                'location_id' => 2,
                'worker_id' => 2,
                'title' => 'Midwife'
            ])
        ];
    }

    protected function generateJobs()
    {
        return [
            EloquentTestJob::create([
                'id' => 1,
                'label' => 'forager'
            ]),

            EloquentTestJob::create([
                'id' => 2,
                'label' => 'soldier'
            ]),

            EloquentTestJob::create([
                'id' => 3,
                'label' => 'attendant'
            ])
        ];
    }

    protected function generateLocations()
    {
        return [
            EloquentTestLocation::create([
                'id' => 1,
                'name' => 'Hatchery'
            ]),

            EloquentTestLocation::create([
                'id' => 2,
                'name' => 'Brood Chamber'
            ])
        ];
    }

    protected function generateWorkers()
    {
        return [
            EloquentTestWorker::create([
                'id' => 1,
                'name' => 'Alice'
            ]),

            EloquentTestWorker::create([
                'id' => 2,
                'name' => 'David'
            ])
        ];
    }

    protected function getTaskFailureMessage($taskNumber)
    {
        return "((+_+)) This test will pass when task $taskNumber has been implemented correctly.";
    }

    protected function getTaskSuccessMessage($test)
    {
        echo PHP_EOL . "(^_^) Success!  '$test' passed." . PHP_EOL;
    }
}

/**
 * Eloquent Models...
 */
class EloquentTestModel extends Model
{
    protected $connection = 'default';

    public $timestamps = false;
}

class EloquentTestWorker extends EloquentTestModel
{
    protected $table = 'workers';
    protected $guarded = [];

    /**
     * Get all of the worker's unique jobs based on their assignments.
     */
    public function assignments()
    {
        $relation = $this->belongsToTernary(
            EloquentTestJob::class,
            'assignments',
            'worker_id',
            'job_id'
        );

        return $relation;
    }

    /**
     * Get all jobs to which this worker is assigned.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function jobs()
    {
        return $this->belongsToMany(EloquentTestJob::class, 'job_workers', 'worker_id', 'job_id');
    }
}

class EloquentTestAssignment extends EloquentTestModel
{
    protected $table = 'assignments';
    protected $guarded = [];

    /**
     * Get the job for this assignment.
     */
    public function job()
    {
        return $this->belongsTo(EloquentTestAssignment::class, 'job_id');
    }
}

class EloquentTestJob extends EloquentTestModel
{
    protected $table = 'jobs';
    protected $guarded = [];
}

class EloquentTestLocation extends EloquentTestModel
{
    protected $table = 'locations';
    protected $guarded = [];
}
