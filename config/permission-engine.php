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

];
