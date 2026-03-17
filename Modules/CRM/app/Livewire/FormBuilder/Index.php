<?php

namespace Modules\CRM\Livewire\FormBuilder;

use Livewire\Component;
use Modules\CRM\Models\Form;

class Index extends Component
{
    public string $name = '';

    public string $schema = '{"fields":[]}';

    protected $rules = [
        'name' => 'required|string|max:255',
        'schema' => 'required|string',
    ];

    public function save(): void
    {
        $this->validate();

        $decoded = json_decode($this->schema, true);

        if (! is_array($decoded)) {
            $this->addError('schema', __('Schema must be valid JSON object.'));
            return;
        }

        Form::create([
            'name' => $this->name,
            'schema' => $decoded,
            'owner_id' => auth()->id(),
        ]);

        $this->reset('name');
        $this->schema = '{"fields":[]}';

        session()->flash('success', __('Form saved'));
    }

    public function render()
    {
        $forms = Form::query()->latest()->paginate(10);

        return view('crm::form-builder.index', [
            'forms' => $forms,
        ]);
    }
}
