<x-filament-panels::page>
    @php
        $status = $monitoringSnapshot['status'] ?? 'healthy';
        $summary = $monitoringSnapshot['summary'] ?? [];
        $statusKey = $status === 'healthy' ? 'healthy' : 'degraded';
        $timezone = auth()->user()?->timezone ?? config('app.timezone');
        $refreshedAt = isset($monitoringSnapshot['refreshed_at'])
            ? \Illuminate\Support\Carbon::parse($monitoringSnapshot['refreshed_at'])->timezone($timezone)->format('Y-m-d H:i:s')
            : trans('monitoring::monitoring.status.unknown_time');
    @endphp

    <div class="mb-6 rounded-xl border px-4 py-3 @if($status === 'healthy') border-success-200 bg-success-50 text-success-900 @else border-warning-200 bg-warning-50 text-warning-900 @endif">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-sm font-semibold">
                    {{ trans('monitoring::monitoring.status.heading') }}
                </h2>
                <p class="text-sm">
                    {{ trans('monitoring::monitoring.status.summary', ['status' => trans("monitoring::monitoring.status.{$statusKey}"), 'time' => $refreshedAt]) }}
                </p>
            </div>

            <p class="text-sm">
                {{ trans('monitoring::monitoring.status.counts', ['offline' => $summary['nodes_offline'] ?? 0, 'errors' => ($summary['node_errors'] ?? 0) + ($summary['server_errors'] ?? 0)]) }}
            </p>
        </div>

        @if (! empty($monitoringSnapshot['messages']))
            <ul class="mt-3 space-y-1 text-sm">
                @foreach (array_slice($monitoringSnapshot['messages'], 0, 4) as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</x-filament-panels::page>
