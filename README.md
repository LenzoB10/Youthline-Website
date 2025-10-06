# VILT Starter Kit for Malawi University of Science and Arts (MUST)

This repository serves as a starter kit for building systems at the Malawi University of Science and Arts (MUST) using the VILT stack (Vue, Inertia, Laravel, Tailwind).

## Features

- **Laravel 12**: Latest Laravel framework for robust backend development.
- **Inertia.js v2**: Seamless integration of Vue.js with Laravel for building single-page applications.
- **Vue 3**: Modern reactive frontend framework with Composition API support.
- **Tailwind CSS 3**: Utility-first CSS framework for designing modern user interfaces.
- **Authentication**: Managed via Laravel Sanctum and Google OAuth.
- **Role and Permission Management**: Powered by Spatie's Laravel Permission package.
- **Media Management**: Handled by Spatie's Laravel Media Library.
- **Activity Logging**: Implemented using Spatie's Activity Log package.
- **Development Tools**: Includes Laravel Telescope for debugging and Laravel Breeze for authentication scaffolding.
- **Data Display**: Built-in HasDataTable trait for efficient server-side data management.
- **Testing**: Comprehensive test suite using Pest PHP for unit and feature testing.

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/mustdevelopers/must-system-starter-kit-vilt.git
   ```

2. Navigate to the project directory:
   ```bash
   cd must-system-starter-kit-vilt
   ```

3. Install PHP dependencies:
   ```bash
   composer install
   ```

4. Install JavaScript dependencies:
   ```bash
   npm install
   ```

5. Create environment file:
   ```bash
   cp .env.example .env
   ```

6. Generate application key:
   ```bash
   php artisan key:generate
   ```

7. Configure your database in the `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

8. Run migrations and seed the database:
   ```bash
   php artisan migrate --seed
   ```

9. Build frontend assets:
   ```bash
   npm run build
   ```

10. Start the development server:
    ```bash
    composer run dev
    ```
    
    This command will start both the Laravel development server and Vite for hot module replacement.

11. Access the application at `http://localhost:8000`

## Google Authentication

The application supports Google OAuth for user authentication. Users can log in or register using their Google accounts. The authentication process is handled by the GoogleAuthController, which uses Laravel Socialite to manage OAuth redirection and callback handling. Activity logging is also integrated to track user registration and login events.

### Setting up Google OAuth

1. Create a project in [Google Cloud Console](https://console.cloud.google.com/)
2. Enable Google+ API
3. Create OAuth 2.0 credentials
4. Add the following to your `.env` file:
   ```env
   GOOGLE_CLIENT_ID=your-client-id
   GOOGLE_CLIENT_SECRET=your-client-secret
   GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
   ```

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code formatting. Pint ensures consistent code style across the project.

### Running Pint

Format all PHP files:
```bash
./vendor/bin/pint
```

Check code style without making changes:
```bash
./vendor/bin/pint --test
```

Format specific files or directories:
```bash
./vendor/bin/pint app/Models
```

## Development Workflow

### Recommended Workflow for New Features

1. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Write tests first** (Test-Driven Development):
   ```bash
   php artisan make:test Feature/YourFeatureTest --pest
   ```

3. **Implement the feature**:
   - Create models: `php artisan make:model YourModel -mf`
   - Create controllers: `php artisan make:controller YourController`
   - Create requests: `php artisan make:request YourRequest`

4. **Run tests to ensure everything works**:
   ```bash
   php artisan test --filter=YourFeature
   ```

5. **Format your code**:
   ```bash
   ./vendor/bin/pint
   ```

6. **Run full test suite**:
   ```bash
   php artisan test
   ```

7. **Commit and push your changes**:
   ```bash
   git add .
   git commit -m "Add: your feature description"
   git push origin feature/your-feature-name
   ```

## Common Artisan Commands

### Database
```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh --seed # Fresh migration with seeding
php artisan migrate:rollback     # Rollback last migration
php artisan db:seed              # Run database seeders
```

### Code Generation
```bash
php artisan make:model ModelName -mfsc    # Model with migration, factory, seeder, controller
php artisan make:controller ControllerName --resource
php artisan make:request RequestName
php artisan make:test TestName --pest
```

### Caching
```bash
php artisan cache:clear          # Clear application cache
php artisan config:clear         # Clear config cache
php artisan route:clear          # Clear route cache
php artisan view:clear           # Clear compiled views
php artisan optimize:clear       # Clear all caches
```

### Development
```bash
php artisan serve                # Start development server
php artisan tinker              # Interactive REPL
php artisan telescope:prune     # Prune old Telescope entries
```

## Project Structure

```
├── app/
│   ├── Console/Commands/       # Custom Artisan commands
│   ├── Http/
│   │   ├── Controllers/       # Application controllers
│   │   ├── Middleware/        # Custom middleware
│   │   └── Requests/          # Form request validation
│   ├── Models/                # Eloquent models
│   ├── Notifications/         # Email/SMS notifications
│   ├── Traits/                # Reusable traits (HasDataTable, LogsActivityTrait)
│   └── Providers/             # Service providers
├── database/
│   ├── factories/             # Model factories for testing
│   ├── migrations/            # Database migrations
│   └── seeders/               # Database seeders
├── resources/
│   ├── js/
│   │   ├── Components/        # Vue components
│   │   ├── Layouts/           # Layout components
│   │   └── Pages/             # Inertia pages
│   ├── css/                   # Stylesheets
│   └── views/                 # Blade templates (minimal, mainly for emails)
├── routes/
│   ├── web.php               # Web routes
│   ├── auth.php              # Authentication routes
│   └── console.php           # Console routes
└── tests/
    ├── Feature/              # Feature tests
    └── Unit/                 # Unit tests
```

## Troubleshooting

### Frontend not updating
If your frontend changes aren't reflecting:
```bash
npm run build
# or
composer run dev  # For development with HMR
```

### Database issues
```bash
php artisan migrate:fresh --seed  # Reset database
php artisan optimize:clear        # Clear all caches
```

### Permission issues
```bash
php artisan permission:cache-reset  # Reset permission cache
```

### Test failures
```bash
php artisan config:clear  # Clear config cache
php artisan test          # Re-run tests
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Write tests for your feature
4. Implement your feature
5. Run tests and ensure they pass (`php artisan test`)
6. Format your code (`./vendor/bin/pint`)
7. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
8. Push to the branch (`git push origin feature/AmazingFeature`)
9. Open a Pull Request

## License

This project is proprietary software owned by the Malawi University of Science and Arts (MUST).

## Support

For support, please contact the ICT Department at MUST or raise an issue in the repository.

## Testing

This starter kit uses [Pest PHP](https://pestphp.com/) for testing, providing an elegant testing experience with a clean, expressive syntax.

### Running Tests

Run all tests:
```bash
php artisan test
```

Run tests with coverage:
```bash
php artisan test --coverage
```

Run specific test file:
```bash
php artisan test tests/Feature/Models/UserTest.php
```

Run tests matching a specific name:
```bash
php artisan test --filter="it can create a user"
```

Run only unit tests:
```bash
php artisan test --testsuite=Unit
```

Run only feature tests:
```bash
php artisan test --testsuite=Feature
```

### Writing Tests

Tests are located in the `tests/` directory:
- `tests/Unit/` - Unit tests for individual classes and methods
- `tests/Feature/` - Feature tests for application workflows

Example Pest test:
```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a user with factory', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->email)->toBeString();
});
```

### Test Best Practices

1. **Use RefreshDatabase**: Always use the `RefreshDatabase` trait to ensure a clean database state for each test.
2. **Use Factories**: Leverage model factories for creating test data instead of manual creation.
3. **Descriptive Test Names**: Use clear, descriptive test names that explain what is being tested.
4. **Test All Paths**: Test happy paths, failure paths, and edge cases.
5. **Run Tests Before Committing**: Always run the full test suite before pushing changes.

## Using DataTables

This starter kit includes a powerful DataTable system with a reusable `HasDataTable` trait and Vue component for implementing server-side data tables with search, filter, sort, and pagination capabilities.

### Quick Start - Controller Usage

In your controller, use the `HasDataTable` trait and call `processDataTable()`:

```php
use App\Http\Requests\DatatableRequest;
use App\Traits\HasDataTable;
use App\Models\User;

class UsersController extends Controller
{
    use HasDataTable;

    public function index(DatatableRequest $request)
    {
        $query = User::query();

        $users = $this->processDataTable(
            $query,
            $request,
            ['name', 'email', 'status'],  // Searchable fields
            ['status' => 'status']         // Filters: 'filter_name' => 'column_name'
        );

        $users->appends($request->query());

        return inertia('Users/Index', [
            'users' => $users,
            'filters' => $request->all(),
        ]);
    }
}
```

### Frontend Usage - Vue Component

Use the pre-built `DataTable` component:

```vue
<template>
    <DataTable
        :data="users"
        :columns="[
            { key: 'name', label: 'Name' },
            { key: 'email', label: 'Email' },
            { key: 'status', label: 'Status' },
            { key: 'created_at', label: 'Joined' },
        ]"
        :route-name="route().current()"
        :filters-config="{
            status: {
                component: 'select',
                label: 'Status',
                options: [
                    { value: 'active', label: 'Active' },
                    { value: 'inactive', label: 'Inactive' },
                ],
                placeholder: 'Filter by status',
            }
        }"
    >
        <!-- Custom column rendering -->
        <template #column-status="{ item }">
            <span class="badge" :class="item.status == 'active' ? 'badge-green' : 'badge-red'">
                {{ item.status }}
            </span>
        </template>

        <!-- Actions column -->
        <template #actions="{ item }">
            <Link :href="route('admin.users.show', item.id)">View</Link>
            <button @click="editUser(item)">Edit</button>
            <button @click="deleteUser(item)">Delete</button>
        </template>
    </DataTable>
</template>
```

### Key Features

- ✅ **Server-side pagination** - Efficient handling of large datasets
- ✅ **Multi-field search** - Search across multiple columns simultaneously  
- ✅ **Dynamic filters** - Select dropdowns or text inputs
- ✅ **Column sorting** - Click headers to sort ascending/descending
- ✅ **Custom rendering** - Use slots for custom column display
- ✅ **Responsive design** - Table view (desktop) / Card view (mobile)
- ✅ **URL state** - Filters persist in URL for shareable links
- ✅ **Preserve scroll** - Maintains position during pagination

### Advanced Filter Examples

```php
// Simple column filter
'status' => 'status',

// Custom callback filter
'email_verified' => fn($query, $value) => 
    $query->whereNotNull('email_verified_at'),

// Date range filter
'created_after' => fn($query, $value) => 
    $query->where('created_at', '>=', $value),
```

## Complete DataTable Example

Here's how the DataTable system works in practice, using the Users module:

### How It Works

**Request Flow:**
```
User types search → DataTable component → Inertia GET request → 
Controller → HasDataTable trait → Database → Response → Rendered table
```

**Example URL with filters:**
```
/admin/users?search=john&status=active&sort_by=name&sort_direction=asc&per_page=25&page=1
```

**Generated SQL:**
```sql
SELECT * FROM users 
WHERE (name LIKE '%john%' OR email LIKE '%john%' OR status LIKE '%john%')
  AND status = 'active'
ORDER BY name ASC
LIMIT 25 OFFSET 0
```

### Adding DataTable to Your Own Models

1. **Add DatatableRequest validation** (optional - add custom filters):
```php
// app/Http/Requests/DatatableRequest.php
public function rules(): array
{
    return [
        'search' => 'nullable|string|max:100',
        'status' => 'nullable|string|in:active,inactive',
        'sort_by' => 'nullable|string',
        'sort_direction' => 'nullable|in:asc,desc',
        'per_page' => 'nullable|integer|min:1|max:500',
    ];
}
```

2. **Controller** - Use the HasDataTable trait:
```php
use App\Traits\HasDataTable;
use App\Http\Requests\DatatableRequest;

class YourController extends Controller
{
    use HasDataTable;

    public function index(DatatableRequest $request)
    {
        $items = $this->processDataTable(
            YourModel::query(),
            $request,
            ['name', 'email'],    // Searchable fields
            ['status' => 'status'] // Filters
        );

        return inertia('YourPage', ['items' => $items]);
    }
}
```

3. **Vue Component** - Use the DataTable component:
```vue
<DataTable
    :data="items"
    :columns="[
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email' },
    ]"
    :route-name="route().current()"
/>
```

That's it! You now have a fully functional datatable with search, filter, sort, and pagination.

   
