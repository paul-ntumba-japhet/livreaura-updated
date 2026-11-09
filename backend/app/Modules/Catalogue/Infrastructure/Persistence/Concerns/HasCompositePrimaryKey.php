<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasCompositePrimaryKey
{
    /**
     * @return list<string>
     */
    abstract public function compositePrimaryKey(): array;

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyName(): string
    {
        return $this->compositePrimaryKey()[0];
    }

    protected function setKeysForSaveQuery($query)
    {
        return $this->constrainCompositeKey($query);
    }

    protected function setKeysForSelectQuery($query)
    {
        return $this->constrainCompositeKey($query);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    private function constrainCompositeKey(Builder $query): Builder
    {
        foreach ($this->compositePrimaryKey() as $column) {
            $query->where($column, '=', $this->original[$column] ?? $this->getAttribute($column));
        }

        return $query;
    }
}
