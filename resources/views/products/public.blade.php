<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }} — Product Compliance</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <div class="mx-auto max-w-2xl px-4 py-12">

        {{-- Seal badge --}}
        <div class="mb-8 flex justify-center">
            @if ($sealStatus === \App\Enums\SealStatus::Verified)
                <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-5 py-2.5 text-emerald-800 ring-1 ring-emerald-300">
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                    </svg>
                    <span class="text-sm font-semibold">Verified</span>
                </div>
            @elseif ($sealStatus === \App\Enums\SealStatus::InProgress)
                <div class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-5 py-2.5 text-amber-800 ring-1 ring-amber-300">
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="text-sm font-semibold">Verification in progress</span>
                </div>
            @else
                <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-5 py-2.5 text-gray-600 ring-1 ring-gray-300">
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span class="text-sm font-semibold">Not verified</span>
                </div>
            @endif
        </div>

        {{-- Product card --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-100 px-6 py-5">
                <h1 class="text-xl font-bold tracking-tight">{{ $product->name }}</h1>
                @if ($product->organization)
                    <p class="mt-1 text-sm text-gray-500">by {{ $product->organization->name }}</p>
                @endif
            </div>

            <dl class="divide-y divide-gray-100 px-6">
                @if ($product->category)
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="font-medium text-gray-500">Category</dt>
                        <dd>{{ $product->category->name }}</dd>
                    </div>
                @endif

                @if ($product->supplier)
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="font-medium text-gray-500">Supplier</dt>
                        <dd>{{ $product->supplier->name }}</dd>
                    </div>
                @endif

                @if ($product->brand)
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="font-medium text-gray-500">Brand</dt>
                        <dd>{{ $product->brand->name }}</dd>
                    </div>
                @endif

                @if ($product->ean)
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="font-medium text-gray-500">EAN</dt>
                        <dd>{{ $product->ean }}</dd>
                    </div>
                @endif

                @if ($product->internal_article_number)
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="font-medium text-gray-500">Article number</dt>
                        <dd>{{ $product->internal_article_number }}</dd>
                    </div>
                @endif
            </dl>

            {{-- Compliance summary --}}
            <div class="border-t border-gray-100 px-6 py-5">
                <h2 class="text-sm font-semibold text-gray-700">Compliance</h2>
                <div class="mt-3 flex items-center gap-3">
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200">
                        <div class="h-full rounded-full transition-all {{ (float) $product->completeness_score >= 100 ? 'bg-emerald-500' : ((float) $product->completeness_score >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                             style="width: {{ min((float) $product->completeness_score, 100) }}%"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-600">{{ number_format((float) $product->completeness_score, 0) }}%</span>
                </div>

                @if ($product->documents->isNotEmpty())
                    <div class="mt-4">
                        <h3 class="text-xs font-medium uppercase tracking-wide text-gray-500">Documents on file</h3>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($product->documents->pluck('type')->unique() as $type)
                                <span class="inline-flex rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-200">
                                    {{ $type instanceof \App\Enums\DocumentType ? $type->label() : $type }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <p class="mt-8 text-center text-xs text-gray-400">
            This page is provided for product compliance verification purposes.
        </p>
    </div>
</body>
</html>
