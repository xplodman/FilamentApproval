# FilamentApproval

A Filament plugin for approval/moderation of create/edit/delete operations.

## Installation

You can install the package via composer:

```bash
composer require xplodman/filamentapproval
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filamentapproval-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filamentapproval-config"
```

## Usage

### Basic Setup

1. **Publish and run migrations**:
   ```bash
   php artisan vendor:publish --tag="filamentapproval-migrations"
   php artisan migrate
   ```

2. **Publish config file** (optional):
   ```bash
   php artisan vendor:publish --tag="filamentapproval-config"
   ```

### Using the InterceptsCreateForApproval Trait

#### Option 1: Use the Artisan Command (Recommended)

You can use the provided Artisan command to generate an approval-enabled create page:

```bash
php artisan filamentapproval:make-approval-page PostResource
```

This will create a `CreatePost` page in `app/Filament/Resources/PostResource/Pages/` with the approval trait already included.

#### Option 2: Manual Implementation

To intercept create operations and require approval, add the `InterceptsCreateForApproval` trait to your Filament resource's CreateRecord page:

```php
<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;
use Xplodman\FilamentApproval\Concerns\InterceptsCreateForApproval;

class CreatePost extends CreateRecord
{
    use InterceptsCreateForApproval;
    
    protected static string $resource = PostResource::class;
}
```

#### Update Your Resource

After creating the approval-enabled page, make sure to update your resource to use the new page:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages\CreatePost;
// ... other imports

class PostResource extends Resource
{
    // ... your resource configuration

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'), // Use your new approval-enabled page
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
```

### Managing Approval Requests

The package automatically registers an `ApprovalRequestResource` in your Filament admin panel where administrators can:

- View all pending, approved, and rejected requests
- Filter by status, request type, requester, and model type
- Approve or reject requests
- View the form data that was submitted for approval

### Configuration

You can customize the package behavior by publishing and modifying the config file:

```php
// config/filamentapproval.php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class that will be used for relationships in the
    | approval requests.
    |
    */
    'user_model' => 'App\Models\User',

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The navigation group where the approval requests resource will appear
    | in the Filament admin panel.
    |
    */
    'navigation_group' => 'Management',

    /*
    |--------------------------------------------------------------------------
    | Navigation Icon
    |--------------------------------------------------------------------------
    |
    | The icon that will be displayed next to the approval requests resource
    | in the Filament admin panel navigation.
    |
    */
    'navigation_icon' => 'heroicon-o-clipboard-document-check',

    /*
    |--------------------------------------------------------------------------
    | Auto Register Resource
    |--------------------------------------------------------------------------
    |
    | Whether to automatically register the ApprovalRequestResource with
    | Filament panels. Set to false if you want to manually register it.
    |
    */
    'auto_register_resource' => true,
];
```

### Database Schema

The package creates an `approval_requests` table with the following structure:

- `id` - ULID primary key
- `request_type` - Type of request (create, edit, delete)
- `requester_id` - ID of the user who made the request
- `approvable_type` - The model class being approved
- `approvable_id` - ID of the specific model instance (for edit/delete)
- `attributes` - JSON data of the model attributes
- `relationships` - JSON data of relationships (for future use)
- `original_data` - JSON data of original values (for edit requests)
- `status` - Current status (pending, approved, rejected)
- `decision_by_id` - ID of the user who made the decision
- `decision_reason` - Optional reason for the decision
- `decision_at` - Timestamp of the decision
- `created_at`, `updated_at`, `deleted_at` - Standard timestamps

### How It Works

1. When a user tries to create a record using a resource with the `InterceptsCreateForApproval` trait:
   - The form data is captured
   - An `ApprovalRequest` is created with status "pending"
   - The actual record creation is prevented
   - A notification is shown to the user

2. Administrators can view and manage these requests through the Filament admin panel

3. When an administrator approves a request:
   - The actual model is created with the captured data
   - The approval request is marked as approved
   - The approvable_id is linked to the created model

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Xplodman](https://github.com/xplodman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.