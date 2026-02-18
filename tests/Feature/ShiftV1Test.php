<?php

use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

// shift - default GET
test('shift_v1', function () {
    $response = $this->get('api/backoffice/v1/shift', []);

    $response->assertStatus(200);
});

// shift - get response valid
test('shift_v1_404', function () {
    $response = $this->get('api/backoffice/v1/shifts', []);
    
    $response->assertStatus(404);
});

// shift - get response valid
test('shift_v1_response', function () {
    $response = $this->get('api/backoffice/v1/shift', []);
    
    $response->assertStatus(200);
    $response->assertJsonStructure(['message']);
});

// shift - create without parameter
test('shift_v1_create_without_parameter', function () {
    $response = $this->post('api/backoffice/v1/shift', []);

    $response->assertStatus(200);
});

// shift - create with parameter null
test('shift_v1_create_with_parameter_null', function () {
    $response = $this->post('api/backoffice/v1/shift', []);

    $response->assertStatus(200);
});