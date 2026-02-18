<?php

use App\Models\Machine;
use App\Models\User;

// machine - log entry GET
test('machine_v1', function () {
    $response = $this->get('api/machine/v1/log-entry');

    $response->assertStatus(200);
});

// machine - log entry POST without parameter
test('machine_v1_without_parameter', function () {
    $response = $this->post('api/machine/v1/log-entry', [
    ]);

    $response->assertStatus(403);
});