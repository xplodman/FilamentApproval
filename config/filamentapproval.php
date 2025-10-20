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