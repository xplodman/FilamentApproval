# FilamentApproval

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xplodman/filamentapproval.svg?style=flat-square)](https://packagist.org/packages/xplodman/filamentapproval)
[![Total Downloads](https://img.shields.io/packagist/dt/xplodman/filamentapproval.svg?style=flat-square)](https://packagist.org/packages/xplodman/filamentapproval)
[![GitHub Tests](https://img.shields.io/github/actions/workflow/status/xplodman/filamentapproval/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xplodman/filamentapproval/actions)
[![License](https://img.shields.io/packagist/l/xplodman/filamentapproval.svg?style=flat-square)](https://packagist.org/packages/xplodman/filamentapproval)

A powerful Filament plugin that adds approval workflows to your create, edit, and delete operations. Perfect for content moderation, administrative oversight, and maintaining data integrity in your Filament applications.

## ✨ Features

- 🔄 **Complete CRUD Approval** - Intercept create, edit, and delete operations
- 🎯 **Easy Integration** - Simple traits to add to your existing Filament resources
- 👥 **User-Friendly** - Clear notifications and intuitive approval interface
- 🔐 **Permission-Based** - Configurable bypass permissions for administrators
- 📊 **Rich Management** - Comprehensive approval request management interface
- 🎨 **Filament Native** - Built specifically for Filament v4 with modern UI components
- 🔍 **Detailed Tracking** - Track changes, original data, and approval history
- ⚡ **Performance Optimized** - Efficient database queries and indexing

## 🚀 Installation

You can install the package via Composer:

```bash
composer require xplodman/filamentapproval
```

### Publish Configuration and Migrations

Publish the configuration file:

```bash
php artisan vendor:publish --tag="filamentapproval-config"
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="filamentapproval-migrations"
php artisan migrate
```

### Register the Plugin

Add the plugin to your Filament panel provider:

```php
// app/Providers/Filament/AdminPanelProvider.php
use Filament\Panel;
use Filament\PanelProvider;
use Xplodman\FilamentApproval\FilamentApprovalPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->plugins([
                FilamentApprovalPlugin::make(),
            ]);
    }
}
```

## ⚙️ Configuration

The package comes with a comprehensive configuration file at `config/filamentapproval.php`:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class that will be used for relationships in the
    | approval requests. This should match your application's user model.
    |
    */
    'user_model' => config('auth.providers.users.model', 'App\Models\User'),

    /*
    |--------------------------------------------------------------------------
    | Approval Request Model
    |--------------------------------------------------------------------------
    |
    | The model class that will be used for approval requests.
    | You can override this to use a custom model (e.g., MongoDB model).
    |
    */
    'approval_request_model' => \Xplodman\FilamentApproval\Models\ApprovalRequest::class,

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
    | Filament panels. Set to true if you want automatic registration.
    | For most cases, you should manually register it in your panel.
    |
    */
    'auto_register_resource' => false,

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debug mode to show detailed debug information in the diff view.
    | This includes raw values, cast types, normalization details, and
    | comparison logic information for troubleshooting.
    |
    */
    'debug' => false,

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Configure the permission names used for approving and rejecting
    | approval requests. These should match the permissions created by
    | your authorization layer (e.g., Filament Shield + Spatie Permission).
    |
    */
    'permissions' => [
        // Set to null to bypass explicit permission checks and allow based on policy or open access.
        // To enforce permissions (e.g., with Filament Shield + Spatie Permission), set your keys here
        // and add the same keys to Shield's `custom_permissions` before generating.
        
        'approve' => null,  // e.g., 'approve_approval_requests'
        'reject' => null,   // e.g., 'reject_approval_requests'
        'bypass' => null,   // e.g., 'bypass_approval_requests'
    ],
];
```

## 📖 Usage

### 1. Create Operations

Add the `InterceptsCreateForApproval` trait to your create page:

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

### 2. Edit Operations

Add the `InterceptsEditForApproval` trait to your edit page:

```php
<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\EditRecord;
use Xplodman\FilamentApproval\Concerns\InterceptsEditForApproval;

class EditPost extends EditRecord
{
    use InterceptsEditForApproval;
    
    protected static string $resource = PostResource::class;
}
```

### 3. Resource Configuration

Update your resource to use the approval-enabled pages:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages\CreatePost;
use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Filament\Resources\PostResource\Pages\ListPosts;
use Filament\Resources\Resource;
use Xplodman\FilamentApproval\Enums\RelationTypeEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    /**
     * Define approval relations for relationship handling
     * This method is required if your model has relationships that need to be handled during approval
     */
    public static function approvalRelations(): array
    {
        return [
            'author'    => ['type' => RelationTypeEnum::BELONGS_TO->value, 'field' => 'author_id'],
            'category'  => ['type' => RelationTypeEnum::BELONGS_TO->value, 'field' => 'category_id'],
            'status'    => ['type' => RelationTypeEnum::BELONGS_TO->value, 'field' => 'status_id'],
            'tags'      => ['type' => RelationTypeEnum::BELONGS_TO_MANY->value, 'field' => 'tag_ids'],
            'comments'  => ['type' => RelationTypeEnum::HAS_MANY->value, 'field' => 'comment_ids'],
        ];
    }
}
```

### 4. Relationship Handling

If your model has relationships that need to be handled during approval, define the `approvalRelations()` method in your resource. This method tells the package how to handle different relationship types during the approval process.

#### Available Relation Types

The package supports the following relationship types via `RelationTypeEnum`:

- `BELONGS_TO` - One-to-one and many-to-one relationships
- `BELONGS_TO_MANY` - Many-to-many relationships  
- `MORPH_TO_MANY` - Polymorphic many-to-many relationships
- `HAS_MANY` - One-to-many relationships
- `MORPH_MANY` - Polymorphic one-to-many relationships

#### Example Usage

```php
use Xplodman\FilamentApproval\Enums\RelationTypeEnum;

public static function approvalRelations(): array
{
    return [
        // Belongs to relationships
        'author' => ['type' => RelationTypeEnum::BELONGS_TO->value, 'field' => 'author_id'],
        'category' => ['type' => RelationTypeEnum::BELONGS_TO->value, 'field' => 'category_id'],
        'status' => ['type' => RelationTypeEnum::BELONGS_TO->value, 'field' => 'status_id'],
        
        // Many-to-many relationships
        'tags' => ['type' => RelationTypeEnum::BELONGS_TO_MANY->value, 'field' => 'tag_ids'],
        'categories' => ['type' => RelationTypeEnum::BELONGS_TO_MANY->value, 'field' => 'category_ids'],
        
        // One-to-many relationships
        'comments' => ['type' => RelationTypeEnum::HAS_MANY->value, 'field' => 'comment_ids'],
        'attachments' => ['type' => RelationTypeEnum::HAS_MANY->value, 'field' => 'attachment_ids'],
    ];
}
```

### 5. Approval Request Resource

By default, the approval request resource is not automatically registered. You have two options:

#### Option 1: Enable Auto Registration

Set `auto_register_resource` to `true` in your config:

```php
// config/filamentapproval.php
'auto_register_resource' => true,
```

#### Option 2: Manual Registration (Recommended)

Keep `auto_register_resource` as `false` and manually register the resource in your panel:

```php
// In your panel provider or service provider
use Xplodman\FilamentApproval\Resources\ApprovalRequestResource;

$panel->resources([
    ApprovalRequestResource::class,
    // ... your other resources
]);
```

### 6. Permissions Setup

#### With Filament Shield

If using Filament Shield, add these permissions to your `custom_permissions`:

```php
// config/shield.php
'custom_permissions' => [
    'approve_approval_requests',
    'reject_approval_requests', 
    'bypass_approval_requests',
],
```

Then update your config:

```php
// config/filamentapproval.php
'permissions' => [
    'approve' => 'approve_approval_requests',
    'reject' => 'reject_approval_requests',
    'bypass' => 'bypass_approval_requests',
],
```

#### With Spatie Permission

```php
// Create permissions
Permission::create(['name' => 'approve_approval_requests']);
Permission::create(['name' => 'reject_approval_requests']);
Permission::create(['name' => 'bypass_approval_requests']);

// Assign to roles
$adminRole->givePermissionTo([
    'approve_approval_requests',
    'reject_approval_requests',
    'bypass_approval_requests'
]);
```

## 🗄️ Database Schema

The package creates an `approval_requests` table with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| `id` | ULID | Primary key |
| `request_type` | String | Type of request (create, edit, delete) |
| `requester_id` | BigInt | ID of the user who made the request |
| `approvable_type` | String | The model class being approved |
| `approvable_id` | ULID | ID of the specific model instance |
| `attributes` | JSON | Model attributes data |
| `relationships` | JSON | Relationship data (for future use) |
| `original_data` | JSON | Original values (for edit requests) |
| `resource_class` | String | Filament resource class for form rendering |
| `status` | String | Current status (pending, approved, rejected) |
| `decided_by_id` | BigInt | ID of the user who made the decision |
| `decided_reason` | Text | Optional reason for the decision |
| `decided_at` | Timestamp | When the decision was made |
| `created_at`, `updated_at`, `deleted_at` | Timestamps | Standard Laravel timestamps |

## 🔄 How It Works

### Create Flow
1. User submits a create form
2. Form data is captured and stored in `approval_requests` table
3. Actual record creation is prevented
4. User receives notification that request is pending approval
5. Administrator reviews and approves/rejects the request
6. If approved, the actual model is created with the captured data

### Edit Flow
1. User modifies a record and saves
2. Changes are detected and compared with original data
3. An approval request is created with the proposed changes
4. Original record remains unchanged
5. Administrator reviews the changes
6. If approved, changes are applied to the original record

### Delete Flow
1. User attempts to delete a record
2. Delete action is intercepted
3. An approval request is created for the deletion
4. Record remains in the database
5. Administrator reviews the deletion request
6. If approved, the record is actually deleted

## 🎨 User Experience

- **Clear Notifications**: Users receive immediate feedback when requests are submitted
- **Intuitive Interface**: Approval requests are managed through a familiar Filament resource
- **Change Tracking**: Administrators can see exactly what changes were proposed
- **Permission-Based**: Users with bypass permissions can skip approval workflows
- **Responsive Design**: Works seamlessly across all device sizes

## 🧪 Testing

Run the test suite:

```bash
composer test
```

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🙏 Credits

- [Xplodman](https://github.com/xplodman)
- [All Contributors](../../contributors)

---

**Made with ❤️ for the Filament community**
