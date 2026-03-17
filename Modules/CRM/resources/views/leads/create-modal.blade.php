<div>
    <form wire:submit.prevent="save">
        <div>
            <label>Source</label>
            <input wire:model="source" type="text" />
        </div>
        <div>
            <label>Status</label>
            <select wire:model="status">
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="qualified">Qualified</option>
            </select>
        </div>
        <div>
            <label>Contact ID (optional)</label>
            <input wire:model="contact_id" type="text" />
        </div>
        <div>
            <button type="submit">Save</button>
        </div>
    </form>
</div>
