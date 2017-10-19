<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace App\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Database\Relations\Concerns\Unique;

/**
 * A BelongsToMany relationship that queries through an additional intermediate model.
 *
 * @author Alex Weissman (https://alexanderweissman.com)
 * @link https://github.com/laravel/framework/blob/5.4/src/Illuminate/Database/Eloquent/Relations/BelongsToMany.php
 */
class BelongsToManyThrough extends BelongsToMany
{
    /**
     * The relation through which we are joining.
     *
     * @var Relation
     */
    protected $intermediateRelation;

    /**
     * Create a new belongs to many relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  \Illuminate\Database\Eloquent\Relations\Relation $intermediateRelation
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $relatedKey
     * @param  string  $relationName
     * @return void
     */
    public function __construct(Builder $query, Model $parent, Relation $intermediateRelation, $table, $foreignKey, $relatedKey, $relationName = null)
    {
        $this->intermediateRelation = $intermediateRelation;

        parent::__construct($query, $parent, $table, $foreignKey, $relatedKey, $relationName);
    }

    /**
     * Match the eagerly loaded results to their parents
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        // TODO

        return $models;
    }

    /**
     * Use the intermediate relationship to determine the "parent" pivot key name
     *
     * This is a crazy roundabout way to get the name of the intermediate relation's foreign key.
     * It would be better if BelongsToMany had a simple accessor for its foreign key.
     * @return string
     */
    public function getParentKeyName()
    {
        return $this->intermediateRelation->newExistingPivot()->getForeignKey();
    }

    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @return string
     */
    public function getExistenceCompareKey()
    {
        return $this->intermediateRelation->getQualifiedForeignKeyName();
    }

    /**
     * Add a "via" query to load the intermediate models through which the child models are related.
     *
     * @param string   $viaRelationName
     * @param callable $viaCallback
     * @return $this
     */
    public function withVia($viaRelationName = null, $viaCallback = null)
    {
        $this->tertiaryRelated = $this->intermediateRelation->getRelated();

        // Set tertiary key and related model
        $this->tertiaryKey = $this->foreignKey;

        $this->tertiaryRelationName = is_null($viaRelationName) ? $this->intermediateRelation->getRelationName() . '_via' : $viaRelationName;

        $this->tertiaryCallback = is_null($viaCallback)
                            ? function () {
                                //
                            }
                            : $viaCallback;

        return $this;
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        // Constraint to only load models where the intermediate relation's foreign key matches the parent model
        $intermediateForeignKeyName = $this->intermediateRelation->getQualifiedForeignKeyName();

        return $this->query->whereIn($intermediateForeignKeyName, $this->getKeys($models));
    }

    /**
     * Unset tertiary pivots on a collection or array of models.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    protected function unsetTertiaryPivots(Collection $models)
    {
        foreach ($models as $model) {
            unset($model->pivot->{$this->foreignKey});
        }
    }

    /**
     * Set the join clause for the relation query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    protected function performJoin($query = null)
    {
        $query = $query ?: $this->query;

        parent::performJoin($query);

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.
        $intermediateTable = $this->intermediateRelation->getTable();

        $key = $this->intermediateRelation->getQualifiedRelatedKeyName();

        $query->join($intermediateTable, $key, '=', $this->getQualifiedForeignKeyName());

        return $this;
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        $parentKeyName = $this->getParentKeyName();

        $this->query->where(
            $parentKeyName, '=', $this->parent->getKey()
        );

        return $this;
    }

    /**
     * Get the pivot columns for the relation.
     *
     * "pivot_" is prefixed to each column for easy removal later.
     *
     * @return array
     */
    protected function aliasedPivotColumns()
    {
        $defaults = [$this->foreignKey, $this->relatedKey];
        $aliasedPivotColumns = collect(array_merge($defaults, $this->pivotColumns))->map(function ($column) {
            return $this->table.'.'.$column.' as pivot_'.$column;
        });

        $parentKeyName = $this->getParentKeyName();

        // Add pivot column for the intermediate relation
        $aliasedPivotColumns[] = "{$this->intermediateRelation->getQualifiedForeignKeyName()} as pivot_$parentKeyName";

        return $aliasedPivotColumns->unique()->all();
    }
}
