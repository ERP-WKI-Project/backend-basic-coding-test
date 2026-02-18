<?php

use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

// user - default GET
test('user_v1', function () {
    $response = $this->get('api/backoffice/v1/user', []);

    $response->assertStatus(200);
});

// user - get response valid
test('user_v1_404', function () {
    $response = $this->get('api/backoffice/v1/users', []);
    
    $response->assertStatus(404);
});

// user - get response valid
test('user_v1_response', function () {
    $response = $this->get('api/backoffice/v1/user', []);
    
    $response->assertStatus(200);
    $response->assertJsonStructure(['message']);
});

// user - create without parameter
test('user_v1_create_without_parameter', function () {
    $response = $this->post('api/backoffice/v1/user', []);

    $response->assertStatus(422);
});

// user - create with parameter null
test('user_v1_create_with_parameter', function () {
    $response = $this->post('api/backoffice/v1/user', []);

    $response->assertStatus(422);
});