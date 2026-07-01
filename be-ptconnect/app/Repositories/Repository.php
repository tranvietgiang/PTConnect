<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class Repository
{
    abstract protected function model(): string;

    public function find(int $id): ?Model
    {
        return $this->model()::find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->model()::findOrFail($id);
    }

    public function all(): Collection
    {
        return $this->model()::all();
    }

    public function create(array $data): Model
    {
        return $this->model()::create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function updateById(int $id, array $data): bool
    {
        return $this->model()::where('id', $id)->update($data);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function deleteById(int $id): bool
    {
        return $this->model()::destroy($id);
    }

    public function newQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->model()::query();
    }
}
