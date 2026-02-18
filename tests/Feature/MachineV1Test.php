<?php

use App\Models\machine;
use App\Models\User;
use App\Models\Usermachine;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

// machine - default GET
test('machine_v1', function () {
    $response = $this->get('api/backoffice/v1/machine', []);

    $response->assertStatus(200);
});

// machine - get response valid
test('machine_v1_404', function () {
    $response = $this->get('api/backoffice/v1/machines', []);
    
    $response->assertStatus(404);
});

// machine - get response valid
test('machine_v1_response', function () {
    $response = $this->get('api/backoffice/v1/machine', []);
    
    $response->assertStatus(200);
    $response->assertJsonStructure(['message']);
});

// machine - create without parameter
test('machine_v1_create_without_parameter', function () {
    $response = $this->post('api/backoffice/v1/machine', []);

    $response->assertStatus(200);
});

// machine - create with parameter null
test('machine_v1_create_with_parameter_null', function () {
    $response = $this->post('api/backoffice/v1/machine', []);

    $response->assertStatus(200);
});