<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace App\Database\Models;

use App\Database\Relations\BelongsToManyThrough;
use Illuminate\Database\Eloquent\Model as LaravelModel;

/**
 * Model Class
 *
 * UserFrosting's base data model, from which all UserFrosting data classes extend.
 * @author Alex Weissman (https://alexanderweissman.com)
 */
abstract class Model extends LaravelModel
{
    /**
     * Define a many-to-many 'through' relationship.
     * This is basically hasManyThrough for many-to-many relationships.
     *
     * @param  string  $related
     * @param  string  $through
     * @param  string  $firstJoiningTable
     * @param  string  $firstForeignKey
     * @param  string  $firstRelatedKey
     * @param  string  $secondJoiningTable
     * @param  string  $secondForeignKey
     * @param  string  $secondRelatedKey
     * @param  string  $throughRelation
     * @param  string  $relation
     * @return \UserFrosting\Sprinkle\Core\Database\Relations\BelongsToManyThrough
     */
    public function belongsToManyThrough(
        $related,
        $through,
        $firstJoiningTable = null,
        $firstForeignKey = null,
        $firstRelatedKey = null,
        $secondJoiningTable = null,
        $secondForeignKey = null,
        $secondRelatedKey = null,
        $throughRelation = null,
        $relation = null
    )
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // Create models for through and related
        $through = new $through;
        $related = $this->newRelatedInstance($related);

        if (is_null($throughRelation)) {
            $throughRelation = $through->getTable();
        }

        // If no table names were provided, we can guess it by concatenating the parent
        // and through table names. The two model names are transformed to snake case
        // from their default CamelCase also.
        if (is_null($firstJoiningTable)) {
            $firstJoiningTable = $this->joiningTable($through);
        }

        if (is_null($secondJoiningTable)) {
            $secondJoiningTable = $through->joiningTable($related);
        }

        $firstForeignKey = $firstForeignKey ?: $this->getForeignKey();
        $firstRelatedKey = $firstRelatedKey ?: $through->getForeignKey();
        $secondForeignKey = $secondForeignKey ?: $through->getForeignKey();
        $secondRelatedKey = $secondRelatedKey ?: $related->getForeignKey();

        // This relationship maps the top model (this) to the through model.
        $intermediateRelationship = $this->belongsToMany($through, $firstJoiningTable, $firstForeignKey, $firstRelatedKey, $throughRelation)
            ->withPivot($firstForeignKey);

        // Now we set up the relationship with the related model.
        $query = new BelongsToManyThrough(
            $related->newQuery(), $this, $intermediateRelationship, $secondJoiningTable, $secondForeignKey, $secondRelatedKey, $relation
        );

        return $query;
    }
}
