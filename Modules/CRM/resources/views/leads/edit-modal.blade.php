<div>
    <form wire:submit.prevent="save">
        <div>
            <label>Source</label>
            <input wire:model="lead.source" type="text" />
        </div>
        <div>
            <label>Status</label>
            <select wire:model="lead.status">
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="qualified">Qualified</option>
            </select>
        </div>
        <div>
            <label>Contact ID</label>
            <input wire:model="lead.contact_id" type="text" />
        </div>
        <div>
            <button type="submit">Save</button>
        </div>
    </form>
</div>
