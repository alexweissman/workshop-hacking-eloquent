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

use PHPUnit\Framework\TestCase;

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
        $capsule = new DB;

        $capsule->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:'
        ]);

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
    }

    protected function createSchema()
    {
        $this->schema($this->schemaName)->create('users', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });

        // Users have multiple roles... (m:m)
        $this->schema($this->schemaName)->create('role_users', function ($table) {
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
        });

        $this->schema($this->schemaName)->create('roles', function ($table) {
            $table->increments('id');
            $table->string('slug');
        });

        // And Roles have multiple permissions... (m:m)
        $this->schema($this->schemaName)->create('permission_roles', function ($table) {
            $table->integer('permission_id')->unsigned();
            $table->integer('role_id')->unsigned();
        });

        $this->schema($this->schemaName)->create('permissions', function($table) {
            $table->increments('id');
            $table->string('slug');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema($this->schemaName)->drop('users');
        $this->schema($this->schemaName)->drop('role_users');
        $this->schema($this->schemaName)->drop('roles');
        $this->schema($this->schemaName)->drop('permission_roles');
        $this->schema($this->schemaName)->drop('permissions');

        Relation::morphMap([], false);
    }

    /**
     * Tests...
     */
    public function testBelongsToManyRelationship()
    {
        $this->generateRolesWithPermissions();

        $user = EloquentTestUser::create(['name' => 'David']);

        $user->roles()->attach([1,2]);

        // Test retrieval of via models as well
        $this->assertEquals([
            [
                'id' => 1,
                'slug' => 'forager',
                'pivot' => [
                    'user_id' => 1,
                    'role_id' => 1
                ]
            ],
            [
                'id' => 2,
                'slug' => 'soldier',
                'pivot' => [
                    'user_id' => 1,
                    'role_id' => 2
                ]
            ]
        ], $user->roles->toArray());
    }

    /**
     * Test the ability of a BelongsToManyThrough relationship to retrieve structured data on a single model or set of models.
     */
    public function testBelongsToManyThrough()
    {
        $this->generateRolesWithPermissions();

        $user = EloquentTestUser::create(['name' => 'David']);

        $user->roles()->attach([1,2]);

        // Test retrieval of via models as well
        $this->assertEquals([
            [
                'id' => 1,
                'slug' => 'uri_harvest',
                'pivot' => [
                    'user_id' => 1,
                    'permission_id' => 1
                ]
            ],
            [
                'id' => 2,
                'slug' => 'uri_spit_acid',
                'pivot' => [
                    'user_id' => 1,
                    'permission_id' => 2
                ]
            ],
            [
                'id' => 3,
                'slug' => 'uri_slash',
                'pivot' => [
                    'user_id' => 1,
                    'permission_id' => 3
                ]
            ]
        ], $user->permissions->toArray());

        // Test counting
        $this->assertEquals(3, $user->permissions()->count());

        $user2 = EloquentTestUser::create(['name' => 'Alex']);
        $user2->roles()->attach([2,3]);

        // Test eager load
        $users = EloquentTestUser::with('permissions')->get();
        $usersWithPermissions = $users->toArray();

        $this->assertEquals([
            [
                'id' => 2,
                'slug' => 'uri_spit_acid',
                'pivot' => [
                    'user_id' => 2,
                    'permission_id' => 2
                ]
            ],
            [
                'id' => 3,
                'slug' => 'uri_slash',
                'pivot' => [
                    'user_id' => 2,
                    'permission_id' => 3
                ]
            ],
            [
                'id' => 4,
                'slug' => 'uri_royal_jelly',
                'pivot' => [
                    'user_id' => 2,
                    'permission_id' => 4
                ]
            ]
        ],$usersWithPermissions[1]['permissions']);

        // Test counting related models (withCount)
        $users = EloquentTestUser::withCount('permissions')->get();
        $this->assertEquals(3, $users[0]->permissions_count);
        $this->assertEquals(3, $users[1]->permissions_count);

        $this->assertInstanceOf(EloquentTestPermission::class, $users[0]->permissions[0]);
        $this->assertInstanceOf(EloquentTestPermission::class, $users[0]->permissions[1]);
        $this->assertInstanceOf(EloquentTestPermission::class, $users[0]->permissions[2]);
    }

    /**
     * Test the ability of a BelongsToManyThrough relationship to retrieve and count paginated queries.
     */
    public function testBelongsToManyThroughPaginated()
    {
        $this->generateRolesWithPermissions();

        $user = EloquentTestUser::create(['name' => 'David']);

        $user->roles()->attach([1,2]);

        $paginatedPermissions = $user->permissions()->take(2)->offset(1);

        $this->assertEquals([
            [
                'id' => 2,
                'slug' => 'uri_spit_acid',
                'pivot' => [
                    'user_id' => 1,
                    'permission_id' => 2
                ]
            ],
            [
                'id' => 3,
                'slug' => 'uri_slash',
                'pivot' => [
                    'user_id' => 1,
                    'permission_id' => 3
                ]
            ]
        ], $paginatedPermissions->get()->toArray());

        $this->assertEquals(2, $paginatedPermissions->count());
    }

    /**
     * Test the ability of a BelongsToManyThrough relationship to retrieve structured data on a single model or set of models,
     * eager loading the "via" models at the same time.
     */
    public function testBelongsToManyThroughWithVia()
    {
        $this->generateRolesWithPermissions();

        $user = EloquentTestUser::create(['name' => 'David']);

        $user->roles()->attach([1,2]);

        // Test retrieval of via models as well
        $this->assertBelongsToManyThroughForDavid($user->permissions()->withVia('roles_via')->get()->toArray());

        $user2 = EloquentTestUser::create(['name' => 'Alex']);
        $user2->roles()->attach([2,3]);

        // Test eager loading
        $users = EloquentTestUser::with(['permissions' => function ($query) {
            return $query->withVia('roles_via');
        }])->get();

        $this->assertInstanceOf(EloquentTestPermission::class, $users[0]->permissions[0]);
        $this->assertInstanceOf(EloquentTestRole::class, $users[0]->permissions[0]->roles_via[0]);

        $usersWithPermissions = $users->toArray();

        $this->assertBelongsToManyThroughForDavid($usersWithPermissions[0]['permissions']);
        $this->assertBelongsToManyThroughForAlex($usersWithPermissions[1]['permissions']);
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

    protected function generateRoles()
    {
        return [
            EloquentTestRole::create([
                'id' => 1,
                'slug' => 'forager'
            ]),

            EloquentTestRole::create([
                'id' => 2,
                'slug' => 'soldier'
            ]),

            EloquentTestRole::create([
                'id' => 3,
                'slug' => 'egg-layer'
            ])
        ];
    }

    protected function generatePermissions()
    {
        return [
            EloquentTestPermission::create([
                'id' => 1,
                'slug' => 'uri_harvest'
            ]),

            EloquentTestPermission::create([
                'id' => 2,
                'slug' => 'uri_spit_acid'
            ]),

            EloquentTestPermission::create([
                'id' => 3,
                'slug' => 'uri_slash'
            ]),

            EloquentTestPermission::create([
                'id' => 4,
                'slug' => 'uri_royal_jelly'
            ])
        ];
    }

    protected function generateRolesWithPermissions()
    {
        $roles = $this->generateRoles();

        $this->generatePermissions();

        $roles[0]->permissions()->attach([1,2]);
        // We purposefully want a permission that belongs to more than one role
        $roles[1]->permissions()->attach([2,3]);
        $roles[2]->permissions()->attach([2,4]);

        return $roles;
    }

    protected function assertBelongsToManyThroughForDavid($permissions)
    {
        // User should have effective permissions uri_harvest, uri_spit_acid, and uri_slash.
        // We also check that the 'roles_via' relationship is properly set.
        $this->assertEquals('uri_harvest', $permissions[0]['slug']);
        $this->assertArrayHasKey('roles_via', $permissions[0]);
        $this->assertEquals([
            [
                'id' => 1,
                'slug' => 'forager',
                'pivot' => [
                    'permission_id' => 1,
                    'role_id' => 1
                ]
            ]
        ], $permissions[0]['roles_via']);
        $this->assertEquals('uri_spit_acid', $permissions[1]['slug']);
        $this->assertArrayHasKey('roles_via', $permissions[1]);
        $this->assertEquals([
            [
                'id' => 1,
                'slug' => 'forager',
                'pivot' => [
                    'permission_id' => 2,
                    'role_id' => 1
                ]
            ],
            [
                'id' => 2,
                'slug' => 'soldier',
                'pivot' => [
                    'permission_id' => 2,
                    'role_id' => 2
                ]
            ]
        ], $permissions[1]['roles_via']);
        $this->assertEquals('uri_slash', $permissions[2]['slug']);
        $this->assertArrayHasKey('roles_via', $permissions[2]);
        $this->assertEquals([
            [
                'id' => 2,
                'slug' => 'soldier',
                'pivot' => [
                    'permission_id' => 3,
                    'role_id' => 2
                ]
            ]
        ], $permissions[2]['roles_via']);
    }

    protected function assertBelongsToManyThroughForAlex($permissions)
    {
        // User should have effective permissions uri_spit_acid, uri_slash, and uri_royal_jelly.
        // We also check that the 'roles_via' relationship is properly set.
        $this->assertEquals('uri_spit_acid', $permissions[0]['slug']);
        $this->assertArrayHasKey('roles_via', $permissions[0]);
        $this->assertEquals([
            [
                'id' => 2,
                'slug' => 'soldier',
                'pivot' => [
                    'permission_id' => 2,
                    'role_id' => 2
                ]
            ],
            [
                'id' => 3,
                'slug' => 'egg-layer',
                'pivot' => [
                    'permission_id' => 2,
                    'role_id' => 3
                ]
            ]
        ], $permissions[0]['roles_via']);
        $this->assertEquals('uri_slash', $permissions[1]['slug']);
        $this->assertArrayHasKey('roles_via', $permissions[1]);
        $this->assertEquals([
            [
                'id' => 2,
                'slug' => 'soldier',
                'pivot' => [
                    'permission_id' => 3,
                    'role_id' => 2
                ]
            ]
        ], $permissions[1]['roles_via']);
        $this->assertEquals('uri_royal_jelly', $permissions[2]['slug']);
        $this->assertArrayHasKey('roles_via', $permissions[2]);
        $this->assertEquals([
            [
                'id' => 3,
                'slug' => 'egg-layer',
                'pivot' => [
                    'permission_id' => 4,
                    'role_id' => 3
                ]
            ]
        ], $permissions[2]['roles_via']);
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

class EloquentTestUser extends EloquentTestModel
{
    protected $table = 'users';
    protected $guarded = [];

    /**
     * Get all roles to which this user belongs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany('App\Tests\EloquentTestRole', 'role_users', 'user_id', 'role_id');
    }

    /**
     * Get all of the permissions this user has, via its roles.
     *
     * @return \UserFrosting\Sprinkle\Core\Database\Relations\BelongsToManyThrough
     */
    public function permissions()
    {
        return $this->belongsToManyThrough(
            'App\Tests\EloquentTestPermission',
            'App\Tests\EloquentTestRole',
            'role_users',
            'user_id',
            'role_id',
            'permission_roles',
            'role_id',
            'permission_id'
        );
    }
}

class EloquentTestEmail extends EloquentTestModel
{
    protected $table = 'emails';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('App\Tests\EloquentTestUser', 'user_id');
    }
}

class EloquentTestPhone extends EloquentTestModel
{
    protected $table = 'phones';
    protected $guarded = [];

    public function phoneable()
    {
        return $this->morphTo();
    }
}

class EloquentTestRole extends EloquentTestModel
{
    protected $table = 'roles';
    protected $guarded = [];

    /**
     * Get a list of permissions assigned to this role.
     */
    public function permissions()
    {
        return $this->belongsToMany('App\Tests\EloquentTestPermission', 'permission_roles', 'role_id', 'permission_id');
    }
}

class EloquentTestPermission extends EloquentTestModel
{
    protected $table = 'permissions';
    protected $guarded = [];

    /**
     * Get a list of roles that have this permission.
     */
    public function roles()
    {
        return $this->belongsToMany('App\Tests\EloquentTestRole', 'permission_roles', 'permission_id', 'role_id');
    }
}
