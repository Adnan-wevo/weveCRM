<div class="p-4 space-y-6">
	<h2 class="text-xl font-semibold">{{ __('CRM Dashboard') }}</h2>

	<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
		<div class="rounded border bg-white p-4">
			<p class="text-sm text-gray-500">{{ __('Total Contacts') }}</p>
			<p class="mt-2 text-2xl font-bold">{{ $stats['contacts'] }}</p>
		</div>
		<div class="rounded border bg-white p-4">
			<p class="text-sm text-gray-500">{{ __('Active Leads') }}</p>
			<p class="mt-2 text-2xl font-bold">{{ $stats['active_leads'] }}</p>
		</div>
		<div class="rounded border bg-white p-4">
			<p class="text-sm text-gray-500">{{ __('Open Deals') }}</p>
			<p class="mt-2 text-2xl font-bold">{{ $stats['open_deals'] }}</p>
		</div>
		<div class="rounded border bg-white p-4">
			<p class="text-sm text-gray-500">{{ __('Logged Calls') }}</p>
			<p class="mt-2 text-2xl font-bold">{{ $stats['calls'] }}</p>
		</div>
	</div>

	<div class="grid gap-4 lg:grid-cols-2">
		<div class="rounded border bg-white p-4">
			<h3 class="mb-3 text-base font-semibold">{{ __('Recent Deals') }}</h3>
			<table class="min-w-full">
				<thead>
					<tr>
						<th class="p-2 text-left">{{ __('Title') }}</th>
						<th class="p-2 text-left">{{ __('Stage') }}</th>
						<th class="p-2 text-left">{{ __('Value') }}</th>
					</tr>
				</thead>
				<tbody>
					@forelse($recentDeals as $deal)
						<tr class="border-t">
							<td class="p-2">{{ $deal->title }}</td>
							<td class="p-2">{{ ucfirst($deal->stage) }}</td>
							<td class="p-2">{{ $deal->currency }} {{ number_format($deal->value ?? 0, 2) }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="3" class="p-2 text-gray-500">{{ __('No deals yet.') }}</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		<div class="rounded border bg-white p-4">
			<h3 class="mb-3 text-base font-semibold">{{ __('Recent Calls') }}</h3>
			<table class="min-w-full">
				<thead>
					<tr>
						<th class="p-2 text-left">{{ __('Contact') }}</th>
						<th class="p-2 text-left">{{ __('Direction') }}</th>
						<th class="p-2 text-left">{{ __('Called At') }}</th>
					</tr>
				</thead>
				<tbody>
					@forelse($recentCalls as $call)
						<tr class="border-t">
							<td class="p-2">{{ optional($call->contact)->name ?? '—' }}</td>
							<td class="p-2">{{ ucfirst($call->direction) }}</td>
							<td class="p-2">{{ optional($call->called_at)->format('Y-m-d H:i') ?? '—' }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="3" class="p-2 text-gray-500">{{ __('No calls yet.') }}</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>
</div>
