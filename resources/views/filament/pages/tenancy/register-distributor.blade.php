<x-filament-panels::page.simple>
    @if ($this->hasPendingInvitations() && (! $this->showRegistrationForm))
        <div style="display: grid; gap: 1.5rem;">
            <div class="fi-section">
                <div class="fi-section-content-ctn">
                    <div class="fi-section-content">
                        <h2 style="font-size: 1.125rem; font-weight: 600;">Pending Invitations</h2>
                        <p style="margin-top: 0.5rem; color: var(--gray-600);">You have invitation{{ count($this->getPendingInvitations()) === 1 ? '' : 's' }} waiting. Accept one to join a distributor, reject invitations you do not want, or continue to register your own distributor.</p>
                    </div>
                </div>
            </div>

            <div style="display: grid; gap: 1rem;">
                @foreach ($this->getPendingInvitations() as $invitation)
                    <div class="fi-section">
                        <div class="fi-section-content-ctn">
                            <div class="fi-section-content" style="display: grid; gap: 1rem;">
                                <div style="display: grid; gap: 0.25rem;">
                                    <h3 style="font-size: 1rem; font-weight: 600;">{{ $invitation->distributor->name }}</h3>
                                    <p style="color: var(--gray-600);">Role: {{ $invitation->role->getLabel() }}</p>
                                @if ($invitation->supplier)
                                        <p style="color: var(--gray-600);">Supplier: {{ $invitation->supplier->name }}</p>
                                @endif
                                    <p style="color: var(--gray-600);">Expires {{ $invitation->expires_at->diffForHumans() }}</p>
                                </div>

                                <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                                    <x-filament::button wire:click="acceptInvitation({{ $invitation->id }})">
                                        Accept
                                    </x-filament::button>

                                    <x-filament::button color="gray" wire:click="rejectInvitation({{ $invitation->id }})">
                                        Reject
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div>
                <x-filament::button color="gray" wire:click="showDistributorRegistrationForm">
                    Register a Distributor Instead
                </x-filament::button>
            </div>
        </div>
    @else
        {{ $this->content }}
    @endif
</x-filament-panels::page.simple>