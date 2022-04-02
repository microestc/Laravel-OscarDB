<?php

namespace Microestc\OscarDB\Query;

use Illuminate\Database\Query\Expression;

class OscarBuilder extends \Illuminate\Database\Query\Builder
{
    /**
     * Add a subquery cross join to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @param  string  $as
     * @return $this
     */
    public function crossJoinSub($query, $as)
    {
        [$query, $bindings] = $this->createSub($query);

        $expression = '('.$query.') '.$this->grammar->wrapTable($as);

        $this->addBinding($bindings, 'join');

        $this->joins[] = $this->newJoinClause($this, 'cross', new Expression($expression));

        return $this;
    }    
}
