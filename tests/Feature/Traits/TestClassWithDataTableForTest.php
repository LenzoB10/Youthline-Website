<?php

namespace Tests\Feature\Traits;

use App\Traits\HasDataTable;

// Exposes the protected HasDataTable methods for testing.
class TestClassWithDataTableForTest
{
    use HasDataTable;

    public function callProcessDataTable(...$args)
    {
        return $this->processDataTable(...$args);
    }

    public function callApplySearch(...$args)
    {
        return $this->applySearch(...$args);
    }

    public function callApplyFilter(...$args)
    {
        return $this->applyFilter(...$args);
    }

    public function callApplySorting(...$args)
    {
        return $this->applySorting(...$args);
    }

    public function callExecutePagination(...$args)
    {
        return $this->executePagination(...$args);
    }

    public function callIsFullTextSearchSupported()
    {
        return $this->isFullTextSearchSupported();
    }

    public function callGetIndexHintForSort($column)
    {
        return $this->getIndexHintForSort($column);
    }
}
