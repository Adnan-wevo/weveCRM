<?php

use App\Models\Lead;

it('creates a lead using factory', function () {
    Lead::factory()->create();

    expect(Lead::count())->toBe(1);
});
