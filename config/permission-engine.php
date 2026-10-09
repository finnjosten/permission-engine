<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model that has the HasRole trait applied.
    |
    */

    'user_model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | User Table
    |--------------------------------------------------------------------------
    |
    | The name of the database table that the role_id column should be added to.
    |
    */

    'user_table' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Permission Prefix Property
    |--------------------------------------------------------------------------
    |
    | Classes that define this property get their `$this->can('action')` calls
    | prefixed during `permission:refresh`, e.g. `resources.action`.
    | Set to null to disable prefixing.
    |
    */

    'prefix_property' => 'permissionPrefix',

];
