<?php

use Illuminate\Testing\Fluent\AssertableJson;

it('serves the OpenAPI JSON', function () {
    $response = $this->get(url(config('scramble.api.path')));

    $response->assertSuccessful();
    $response->assertHeader('content-type');
    $response->assertJson(fn(AssertableJson $json) => $json->has('openapi')->etc());
});

it('serves the API docs UI', function () {
    $response = $this->get(url(config('scramble.ui.path')));

    $response->assertOk();
    $response->assertSee('elements-api', false);
});
