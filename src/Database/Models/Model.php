<?php
/**
 * Hacking Laravel: Custom Relationships in Eloquent
 *
 * @link      https://github.com/alexweissman/phpworld2017
 * @see       https://world.phparch.com/sessions/hacking-laravel-custom-relationships-in-eloquent/
 * @license   MIT
 */
namespace App\Database\Models;

use App\Database\Relations\BelongsToTernary;
use Illuminate\Database\Eloquent\Model as LaravelModel;

/**
 * Model Class
 *
 * An extension of Laravel's Model class, used to implement custom relationships.
 * @author Alex Weissman (https://alexanderweissman.com)
 */
abstract class Model extends LaravelModel
{
    /**
     * Define a ternary (many-to-many-to-many) relationship.
     *
     * Similar to a regular many-to-many relationship, but removes duplicate child objects.
     * Can also retrieve tertiary related models using the `withTertiary` method.
     * {@inheritDoc}
     * @return \App\Database\Relations\BelongsToTernary
     */
    public function belongsToTernary($related, $table = null, $foreignKey = null, $relatedKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $relatedKey = $relatedKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        return new BelongsToTernary(
            $instance->newQuery(), $this, $table, $foreignKey, $relatedKey, $relation
        );
    }
}
