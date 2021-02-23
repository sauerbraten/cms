<?php

namespace Statamic\Http\Resources\CP\Entries;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Statamic\Http\Resources\CP\Concerns\HasRequestedColumns;

class Entries extends ResourceCollection
{
    use HasRequestedColumns;

    protected $blueprint;
    protected $columnPreferenceKey;

    public function collects()
    {
        return app(ListedEntry::class);
    }

    public function blueprint($blueprint)
    {
        $this->blueprint = $blueprint;

        return $this;
    }

    public function columnPreferenceKey($key)
    {
        $this->columnPreferenceKey = $key;

        return $this;
    }

    private function setColumns()
    {
        $columns = $this->blueprint->columns();

        if ($key = $this->columnPreferenceKey) {
            $columns->setPreferred($key);
        }

        $this->columns = $columns->rejectUnlisted()->values();
    }

    public function toArray($request)
    {
        $this->setColumns();

        return [
            'data' => $this->collection->each(function ($entry) {
                $entry
                    ->blueprint($this->blueprint)
                    ->columns($this->requestedColumns());
            }),

            'meta' => [
                'columns' => $this->visibleColumns(),
            ],
        ];
    }
}
