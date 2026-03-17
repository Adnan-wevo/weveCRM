<?php

use App\Models\Contact;

it('creates a contact using factory', function () {
    Contact::factory()->create();

    expect(Contact::count())->toBe(1);
});
