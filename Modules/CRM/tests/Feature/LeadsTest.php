<?php

use Modules\CRM\Models\Lead as ModuleLead;
use App\Models\Lead;

it('creates a lead using factory', function () {
	Lead::factory()->create();

	expect(Lead::count())->toBe(1);
});

