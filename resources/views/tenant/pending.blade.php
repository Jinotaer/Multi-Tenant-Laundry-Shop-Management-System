<x-guest-layout>
    <div class="text-center">
        <div class="mb-4">
            <svg class="mx-auto h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h2 class="text-lg font-semibold text-gray-700 mb-2">Registration Submitted</h2>
        <p class="text-sm text-gray-500 mb-6">Thank you — your shop registration has been submitted and is awaiting administrator approval. We will email you once it is reviewed.</p>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-sm font-medium text-yellow-800 mb-1">What's next?</p>
            <ul class="text-xs text-yellow-700 text-left list-disc list-inside space-y-1">
                <li>An administrator will review your registration</li>
                <li>You'll receive an approval or rejection email</li>
                <li>If approved, your shop domain and database will be created</li>
            </ul>
        </div>

        <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
            Back to Home
        </a>
    </div>
</x-guest-layout>
