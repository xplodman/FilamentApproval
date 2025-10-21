<?php

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
        
        // Examples:
        // 'approve' => 'Approve:ApprovalRequests',           // Shield v4 default
        // 'approve' => 'approve_approval::request',          // Legacy pattern
        // 'approve' => 'approval.approve',                   // Custom permission
        // 'reject' => 'Reject:ApprovalRequests',             // Shield v4 default
        // 'reject' => 'reject_approval::request',            // Legacy pattern
        // 'reject' => 'approval.reject',                     // Custom permission
        // 'bypass' => 'Bypass:ApprovalRequests',             // Shield v4 default
        // 'bypass' => 'bypass_approval::request',            // Legacy pattern
        // 'bypass' => 'approval.bypass',                     // Custom permission
        
        'approve' => null,
        'reject' => null,
        // If set, users who can this permission bypass approval flows (create/edit/delete)
        'bypass' => null,
    ],
];