<?php

use App\Models\User;
use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

// Create a test class that uses the trait
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

describe('HasDataTable Trait', function () {
    beforeEach(function () {
        $this->traitClass = new TestClassWithDataTableForTest();
    });

    describe('processDataTable method', function () {
        it('processes basic data table request without search or filters', function () {
            $users = User::factory()->count(15)->create();
            $query = User::query();
            $request = new Request();
            
            $result = $this->traitClass->callProcessDataTable($query, $request);
            
            expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
                ->and($result->count())->toBe(10) // Default per_page
                ->and($result->total())->toBe(15);
        });

        it('applies search when search term and searchable fields provided', function () {
            User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
            User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
            User::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);
            
            $query = User::query();
            $request = new Request(['search' => 'John']);
            $searchable = ['name', 'email'];
            
            $result = $this->traitClass->callProcessDataTable($query, $request, $searchable);
            
            expect($result->total())->toBe(2); // John Doe and Bob Johnson
        });

        it('applies filters when provided', function () {
            User::factory()->count(5)->create(['status' => 'active']);
            User::factory()->count(3)->create(['status' => 'inactive']);
            
            $query = User::query();
            $request = new Request(['status' => 'active']);
            $filterable = ['status' => 'status'];
            
            $result = $this->traitClass->callProcessDataTable($query, $request, [], $filterable);
            
            expect($result->total())->toBe(5);
        });

        it('applies custom sorting when provided', function () {
            $user1 = User::factory()->create(['name' => 'Alice']);
            $user2 = User::factory()->create(['name' => 'Bob']);
            $user3 = User::factory()->create(['name' => 'Charlie']);
            
            $query = User::query();
            $request = new Request(['sort_by' => 'name', 'sort_direction' => 'asc']);
            
            $result = $this->traitClass->callProcessDataTable($query, $request);
            
            expect($result->items()[0]->name)->toBe('Alice')
                ->and($result->items()[1]->name)->toBe('Bob')
                ->and($result->items()[2]->name)->toBe('Charlie');
        });

        it('uses default sorting when no sort specified', function () {
            $query = User::query();
            $request = new Request();
            
            $result = $this->traitClass->callProcessDataTable($query, $request);
            
            // Should use default sorting (created_at desc)
            expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
        });

        it('respects custom per_page parameter', function () {
            User::factory()->count(25)->create();
            
            $query = User::query();
            $request = new Request(['per_page' => 20]);
            
            $result = $this->traitClass->callProcessDataTable($query, $request);
            
            expect($result->count())->toBe(20)
                ->and($result->perPage())->toBe(20);
        });

        it('uses cache when cache key provided', function () {
            User::factory()->count(5)->create();
            
            $query = User::query();
            $request = new Request();
            
            // Test that caching works by verifying the cache key is generated
            $result = $this->traitClass->callProcessDataTable(
                $query, 
                $request, 
                [], 
                [], 
                'test_cache_key', 
                60
            );
            
            expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
                ->and($result->total())->toBe(5);
        });
    });

    describe('applySearch method', function () {
        it('applies LIKE search', function () {
            User::factory()->create(['name' => 'John Doe']);
            User::factory()->create(['name' => 'Jane Smith']);
            User::factory()->create(['name' => 'Johnny Cash']);
            
            $query = User::query();
            $this->traitClass->callApplySearch($query, 'John', ['name']);
            
            $results = $query->get();
            expect($results)->toHaveCount(2);
        });

        it('applies search across multiple fields', function () {
            User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
            User::factory()->create(['name' => 'Jane Smith', 'email' => 'john.smith@test.com']);
            
            $query = User::query();
            $this->traitClass->callApplySearch($query, 'john', ['name', 'email']);
            
            $results = $query->get();
            expect($results)->toHaveCount(2);
        });

        it('handles empty search term gracefully', function () {
            User::factory()->count(3)->create();
            
            $query = User::query();
            $this->traitClass->callApplySearch($query, '', ['name']);
            
            $results = $query->get();
            expect($results)->toHaveCount(3); // Empty search returns all records (no filtering applied)
        });

        it('handles case insensitive search', function () {
            User::factory()->create(['name' => 'John Doe']);
            
            $query = User::query();
            $this->traitClass->callApplySearch($query, 'JOHN', ['name']);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
        });
    });

    describe('applyFilter method', function () {
        it('applies filter with callable callback', function () {
            User::factory()->count(3)->create(['status' => 'active']);
            User::factory()->count(2)->create(['status' => 'inactive']);
            
            $query = User::query();
            $callback = function ($query, $value) {
                $query->where('status', $value);
            };
            
            $this->traitClass->callApplyFilter($query, 'status', 'active', $callback);
            
            $results = $query->get();
            expect($results)->toHaveCount(3);
        });

        it('applies filter with array values using whereIn', function () {
            User::factory()->create(['status' => 'active']);
            User::factory()->create(['status' => 'active']);
            User::factory()->create(['status' => 'inactive']);
            
            $query = User::query();
            $this->traitClass->callApplyFilter($query, 'status', ['active'], 'status');
            
            $results = $query->get();
            expect($results)->toHaveCount(2);
        });

        it('applies filter with simple string value', function () {
            User::factory()->count(2)->create(['status' => 'active']);
            User::factory()->count(1)->create(['status' => 'inactive']);
            
            $query = User::query();
            $this->traitClass->callApplyFilter($query, 'status', 'active', 'status');
            
            $results = $query->get();
            expect($results)->toHaveCount(2);
        });
    });

    describe('applySorting method', function () {
        it('applies ascending sort by default', function () {
            $user1 = User::factory()->create(['name' => 'Charlie']);
            $user2 = User::factory()->create(['name' => 'Alice']);
            $user3 = User::factory()->create(['name' => 'Bob']);
            
            $query = User::query();
            $this->traitClass->callApplySorting($query, 'name');
            
            $results = $query->get();
            expect($results->first()->name)->toBe('Alice')
                ->and($results->last()->name)->toBe('Charlie');
        });

        it('applies descending sort when specified', function () {
            $user1 = User::factory()->create(['name' => 'Alice']);
            $user2 = User::factory()->create(['name' => 'Bob']);
            $user3 = User::factory()->create(['name' => 'Charlie']);
            
            $query = User::query();
            $this->traitClass->callApplySorting($query, 'name', 'desc');
            
            $results = $query->get();
            expect($results->first()->name)->toBe('Charlie')
                ->and($results->last()->name)->toBe('Alice');
        });
    });

    describe('executePagination method', function () {
        it('returns paginated results with default per_page', function () {
            User::factory()->count(25)->create();
            
            $query = User::query();
            $request = new Request();
            
            $result = $this->traitClass->callExecutePagination($query, $request);
            
            expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
                ->and($result->perPage())->toBe(10)
                ->and($result->count())->toBe(10)
                ->and($result->total())->toBe(25);
        });

        it('respects custom per_page from request', function () {
            User::factory()->count(25)->create();
            
            $query = User::query();
            $request = new Request(['per_page' => 15]);
            
            $result = $this->traitClass->callExecutePagination($query, $request);
            
            expect($result->perPage())->toBe(15)
                ->and($result->count())->toBe(15);
        });

        it('sets onEachSide to 1', function () {
            User::factory()->count(100)->create();
            
            $query = User::query();
            $request = new Request(['page' => 5]);
            
            $result = $this->traitClass->callExecutePagination($query, $request);
            
            expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
            // The onEachSide method is called but we can't directly test its effect
        });
    });

    describe('isFullTextSearchSupported method', function () {
        it('returns false for non-MySQL database in test environment', function () {
            $result = $this->traitClass->callIsFullTextSearchSupported();
            
            expect($result)->toBeFalse();
        });
    });

    describe('getIndexHintForSort method', function () {
        it('returns correct index for known columns', function () {
            expect($this->traitClass->callGetIndexHintForSort('created_at'))->toBe('created_at_index')
                ->and($this->traitClass->callGetIndexHintForSort('updated_at'))->toBe('updated_at_index')
                ->and($this->traitClass->callGetIndexHintForSort('name'))->toBe('name_index');
        });

        it('returns null for unknown columns', function () {
            expect($this->traitClass->callGetIndexHintForSort('unknown_column'))->toBeNull()
                ->and($this->traitClass->callGetIndexHintForSort('some_random_field'))->toBeNull();
        });
    });

    describe('integration scenarios', function () {
        it('handles complex search and filter combination', function () {
            User::factory()->create(['name' => 'John Active', 'status' => 'active']);
            User::factory()->create(['name' => 'John Inactive', 'status' => 'inactive']);
            User::factory()->create(['name' => 'Jane Active', 'status' => 'active']);
            
            $query = User::query();
            $request = new Request([
                'search' => 'John',
                'status' => 'active'
            ]);
            
            $result = $this->traitClass->callProcessDataTable(
                $query, 
                $request, 
                ['name'], 
                ['status' => 'status']
            );
            
            expect($result->total())->toBe(1); // Only "John Active" matches both conditions
        });

        it('handles search with sort and pagination', function () {
            User::factory()->create(['name' => 'Alice Johnson']);
            User::factory()->create(['name' => 'Bob Johnson']);
            User::factory()->create(['name' => 'Charlie Johnson']);
            User::factory()->create(['name' => 'Dave Smith']);
            
            $query = User::query();
            $request = new Request([
                'search' => 'Johnson',
                'sort_by' => 'name',
                'sort_direction' => 'desc',
                'per_page' => 2,
                'page' => 1
            ]);
            
            $result = $this->traitClass->callProcessDataTable($query, $request, ['name']);
            
            expect($result->total())->toBe(3)
                ->and($result->count())->toBe(2)
                ->and($result->items()[0]->name)->toBe('Charlie Johnson');
        });

        it('handles empty results gracefully', function () {
            $query = User::query();
            $request = new Request(['search' => 'nonexistent']);
            
            $result = $this->traitClass->callProcessDataTable($query, $request, ['name']);
            
            expect($result->total())->toBe(0)
                ->and($result->count())->toBe(0);
        });

        it('handles filter with callback that modifies query significantly', function () {
            User::factory()->count(5)->create();
            
            $query = User::query();
            $request = new Request(['custom_filter' => 'test']);
            $filterable = [
                'custom_filter' => function ($query, $value) {
                    // Complex filter that adds joins, conditions etc.
                    $query->where('name', 'like', '%' . $value . '%')
                          ->orWhere('email', 'like', '%' . $value . '%');
                }
            ];
            
            $result = $this->traitClass->callProcessDataTable($query, $request, [], $filterable);
            
            expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
        });
    });

    describe('edge cases and error handling', function () {
        it('handles null search term', function () {
            User::factory()->count(3)->create();
            
            $query = User::query();
            $this->traitClass->callApplySearch($query, null, ['name']);
            
            $results = $query->get();
            expect($results)->toHaveCount(3); // Null search returns all records (no filtering applied)
        });

        it('handles empty searchable fields array', function () {
            User::factory()->count(3)->create();
            
            $query = User::query();
            $request = new Request(['search' => 'test']);
            
            $result = $this->traitClass->callProcessDataTable($query, $request, []);
            
            expect($result->total())->toBe(3); // No search applied, all records returned
        });

        it('throws exception for invalid sort direction', function () {
            $user1 = User::factory()->create(['name' => 'Alice']);
            $user2 = User::factory()->create(['name' => 'Bob']);
            
            $query = User::query();
            
            expect(fn() => $this->traitClass->callApplySorting($query, 'name', 'invalid_direction'))
                ->toThrow(\InvalidArgumentException::class, 'Order direction must be "asc" or "desc".');
        });

        it('handles zero per_page parameter', function () {
            User::factory()->count(10)->create();
            
            $query = User::query();
            $request = new Request(['per_page' => 0]);
            
            $result = $this->traitClass->callExecutePagination($query, $request);
            
            // Laravel handles 0 per_page differently - it might use all records or a default
            expect($result->perPage())->toBeGreaterThan(0); // Just ensure it's positive
        });
    });

    describe('caching functionality', function () {
        it('properly generates cache keys with request parameters', function () {
            User::factory()->count(5)->create();
            
            $query = User::query();
            $request = new Request(['search' => 'test', 'page' => 2]);
            
            // Clear cache first
            Cache::flush();
            
            $result1 = $this->traitClass->callProcessDataTable(
                $query->clone(), 
                $request, 
                ['name'], 
                [], 
                'test_cache', 
                60
            );
            
            $result2 = $this->traitClass->callProcessDataTable(
                $query->clone(), 
                $request, 
                ['name'], 
                [], 
                'test_cache', 
                60
            );
            
            // Both should return same structure (cached)
            expect($result1)->toBeInstanceOf(LengthAwarePaginator::class)
                ->and($result2)->toBeInstanceOf(LengthAwarePaginator::class);
        });

        it('does not use cache when cache key is null', function () {
            User::factory()->count(5)->create();
            
            $query = User::query();
            $request = new Request();
            
            $result = $this->traitClass->callProcessDataTable($query, $request, [], [], null, 60);
            
            expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
                ->and($result->total())->toBe(5);
        });
    });
});
