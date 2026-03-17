<div>
    <form wire:submit.prevent="save">
        <div>
            <label>Name</label>
            <input wire:model="contact.name" type="text" />
        </div>
        <div>
            <label>Email</label>
            <input wire:model="contact.email" type="email" />
        </div>
        <div>
            <label>Phone</label>
            <input wire:model="contact.phone" type="text" />
        </div>
        <div>
            <label>Company</label>
            <input wire:model="contact.company" type="text" />
        </div>
        <div>
            <button type="submit">Save</button>
        </div>
    </form>
</div>
