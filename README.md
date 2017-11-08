# Hacking Laravel: Custom Relationships in Eloquent

Alexander Weissman
php[world] 2017

In this workshop, you will learn how Laravel's Eloquent ORM is structured internally.  Specifically, we will focus on how it implements relationships, and learn to implement our own custom relationships.

## Background

In this workshop, we will implement a **ternary relationship** - a type of relationship that, while common in business data models, is not directly supported by Laravel.  The ternary relationship implements an m:m:m relationship among three distinct entities.  Consider for example, a model where worker ants have various jobs at various work sites - for example, perhaps the same worker is a soldier at worksite A, but a forager at site B.  To model this, we have a pivot table containing triplets of worker-job-location, which we will call an **assignment**:

**workers**

| id | name   |
|----|--------|
| 1  | Alice  |
| 2  | David  |

**jobs**

| id | description |
|----|-------------|
| 1  | forager     |
| 2  | soldier     |
| 3  | attendant   |

**locations**

| id | name          |
|----|---------------|
| 1  | Hatchery      |
| 2  | Royal Chamber |

**assignments**

| worker_id | job_id | location_id |
|-----------|--------|-------------|
| 1         | 2      | 1           |
| 1         | 2      | 2           |
| 1         | 3      | 2           |
| 2         | 3      | 1           |

## Goal

The goal of this workshop is to implement a `BelongsToTernary` relationship, which can capture this relationship as a nested data structure.  For example we might want to retrieve a worker's jobs, and then for each of their jobs, have a nested sub-collection of the locations where they have those jobs:

Worker Alice's assignments:

```
[
  2 => [
    'description' => 'soldier',
    'locations' => [
      1 => [
        'name' => 'Hatchery'
      ],
      2 => [
        'name' => 'Royal Chamber'
      ]
    ]
  ],
  3 => [
    'description' => 'attendant',
    'locations' => [
      2 => [
        'name' => 'Royal Chamber'
      ]
    ]
  ]
]
```

Alice is a soldier in both the Hatchery and the Royal Chamber, but an attendant only in the Royal Chamber.

This repository includes a partially-implemented version of `BelongsToTernary`, which extends Laravel's `BelongsToMany` relationship.  We also provide an extended version of the `Model` class with a `belongsToTernary` method, and a PHPUnit test suite with (mostly) failing tests.  Your task will be to complete the implementation of `BelongsToTernary` so that all of the tests pass.

## Setup

To make getting started as easy as possible, we will use Eloquent as a standalone package.  We have pre-installed all Composer dependencies - all you need to do is clone or download the repository to a local environment that has PHP, sqlite, and a webserver such as nginx or Apache installed.

The relevant classes may be found in `src/Database`, and have already been mapped as PSR-4 namespaces in `composer.json`.  To run the tests, you have two options:

1. Run `phpunit` from the command line, in the project directory;
2. Visit `public/` in your browser.

At first all tests should fail except `testBelongsToMany`, which is there only as a sanity check.

The tests are run on a temporary in-memory sqlite database, which is destroyed and recreated between each test.  If you would prefer to use an alternative database driver, you may configure it in `config/database.yaml`.  See the [Laravel documentation](https://laravel.com/docs/5.4/database) for configuration details.

To aid in your development process, all executed database queries will be logged to `log/queries.log`.  If you are running the tests in your browser, the query log will be dumped to the response, directly after the PHPUnit test results, rather than the log file.

## Tasks

Your first tasks are to get the top-level relationship working between `Worker` and `Job`.  This is essentially the `BelongsToMany` relationship, except that you must deal with any `Job` that would otherwise appear multiple times due to the fact that there could be multiple triplets with the same `worker_id` and `job_id`.

### 1

Implement `BelongsToTernary::condenseModels`, which collapses these rows into a single model.  For now, don't worry about extracting the tertiary models (locations) for the sub-relationship.

### 2

Modify `BelongsToTernary::match`, which is responsible for matching eager-loaded models to their parents.  Again, we have provided you with the default implementation from `BelongsToMany::match`, but you must modify it to collapse rows with the same `worker_id` and `job_id` (for example) into a single child model.

### 3

By default, `BelongsToTernary::buildDictionary` returns a dictionary that maps parent models to their children.  Modify it so that it also returns a `nestedDictionary`, which maps parent->child->tertiary models.  For example:

```
[
    // Worker 1
    '1' => [
        // Job 3
        '3' => [
            Location1,
            Location2
        ],
        ...
    ],
    ...
]
```

You will also need to further modify `condenseModels` to retrieve the tertiary dictionary and call `matchTertiaryModels` to match the tertiary models with each of the child models, if `withTertiary` is being used.
