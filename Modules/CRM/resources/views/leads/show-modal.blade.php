<div>
    <h3>Lead Details</h3>
    <div>Source: {{ $lead->source }}</div>
    <div>Status: {{ $lead->status }}</div>
    <div>Contact: {{ optional($lead->contact)->name }}</div>
</div>
