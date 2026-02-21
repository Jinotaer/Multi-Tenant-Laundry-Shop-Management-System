<x-guest-layout>
    <div class="text-center">
        <div class="mb-4">
            <svg class="mx-auto h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h2 class="text-lg font-semibold text-gray-700 mb-2">Shop Registered Successfully!</h2>
        <p class="text-sm text-gray-500 mb-6">Your laundry shop has been set up and is ready to use.</p>

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <p class="text-sm text-gray-600">Your shop URL:</p>
            <p class="text-lg font-mono font-semibold text-indigo-600 mt-1">{{ $domain }}</p>
            <p class="text-xs text-gray-400 mt-2">Make sure to add this domain to your hosts file for local development.</p>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 text-left">
            <p class="text-sm font-medium text-yellow-800 mb-1">Local Development Setup</p>
            <p class="text-xs text-yellow-700">Add this line to <code class="bg-yellow-100 px-1 rounded">C:\Windows\System32\drivers\etc\hosts</code>:</p>
            <code class="block mt-2 text-xs bg-yellow-100 p-2 rounded">127.0.0.1 {{ $domain }}</code>
        </div>

        <a href="http://{{ $domain }}:8000/login" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
            Go to Your Shop
        </a>
    </div>
</x-guest-layout>
