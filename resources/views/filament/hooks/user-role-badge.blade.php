@php
    $user = auth()->user();
@endphp

<div class="mt-2 px-2">
    @if($user->roles->count() > 0)
        <div class="text-xs text-gray-500 mb-2">Roles:</div>
        <div class="flex flex-wrap gap-1">
            @foreach($user->roles as $role)
                <span class="inline-flex items-center justify-center min-h-6 px-2 py-0.5 text-xs font-medium tracking-tight rounded-xl whitespace-nowrap text-primary-700 bg-primary-500/10">
                    {{ ucfirst($role->name) }}
                </span>
            @endforeach
        </div>
    @endif
</div>
