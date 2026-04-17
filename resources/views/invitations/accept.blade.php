<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Invitation</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-stone-100 text-stone-950 antialiased">
    <div class="mx-auto flex min-h-screen max-w-3xl items-center px-4 py-12">
        <div class="w-full overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-[0_20px_80px_rgba(28,25,23,0.08)]">
            <div class="border-b border-stone-200 bg-[radial-gradient(circle_at_top_left,rgba(251,191,36,0.22),transparent_45%),linear-gradient(135deg,#fafaf9,#f5f5f4)] px-8 py-8">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-700">Distributor Invitation</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-stone-900">Join {{ $invitation->distributor->name }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-600">
                    This invitation grants <span class="font-medium text-stone-900">{{ $invitation->role->getLabel() }}</span>
                    access for <span class="font-medium text-stone-900">{{ $invitation->email }}</span>.
                </p>
            </div>

            <div class="px-8 py-8">
                @if ($existingUser)
                    <p class="text-sm leading-6 text-stone-600">
                        Confirm the invitation to add this account to the distributor. If you are signed in with a different account, you will be asked to switch first.
                    </p>

                    <form method="POST" action="{{ route('invitation.accept.confirm', ['token' => $invitation->token]) }}" class="mt-8">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">
                            Accept Invitation
                        </button>
                    </form>
                @else
                    <p class="text-sm leading-6 text-stone-600">
                        Continue to registration to create the invited account before joining this distributor.
                    </p>

                    <form method="POST" action="{{ route('invitation.accept.confirm', ['token' => $invitation->token]) }}" class="mt-8">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-amber-500 px-6 py-3 text-sm font-semibold text-stone-950 transition hover:bg-amber-400">
                            Continue to Registration
                        </button>
                    </form>
                @endif

                <p class="mt-6 text-xs text-stone-500">
                    This invitation expires on {{ $invitation->expires_at->format('M j, Y g:i A') }}.
                </p>
            </div>
        </div>
    </div>
</body>
</html>