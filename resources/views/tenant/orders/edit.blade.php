<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Order — {{ $order->order_number }}</h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="max-w-3xl">
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('tenant.orders.update', $order) }}" class="space-y-6"
                    x-data="{
                        serviceId: '{{ old('service_id', $order->service_id ?? '') }}',
                        services: {{ Js::from($services) }},
                        weight: {{ old('weight', $order->weight ?? 0) }},
                        items: {{ json_encode($order->items && count($order->items) ? $order->items : [['name' => '', 'qty' => 1, 'price' => '']]) }},
                        get selectedService() {
                            return this.services.find(s => s.id == this.serviceId);
                        },
                        get serviceBaseTotal() {
                            if (!this.selectedService) return 0;
                            if (this.selectedService.price_type === 'per_kilo') return parseFloat(this.selectedService.price || 0) * parseFloat(this.weight || 0);
                            if (this.selectedService.price_type === 'per_piece') return 0;
                            return parseFloat(this.selectedService.price || 0);
                        },
                        lineItemPrice(item) {
                            if (this.selectedService && this.selectedService.price_type === 'per_piece' && (item.price === '' || item.price === null || typeof item.price === 'undefined')) {
                                return parseFloat(this.selectedService.price || 0);
                            }

                            return parseFloat(item.price || 0);
                        },
                        get itemsTotal() {
                            return this.items.reduce((sum, i) => sum + (parseFloat(i.qty || 0) * this.lineItemPrice(i)), 0);
                        },
                        get total() {
                            return this.serviceBaseTotal + this.itemsTotal;
                        },
                        addItem() {
                            this.items.push({
                                name: '',
                                qty: 1,
                                price: this.selectedService && this.selectedService.price_type === 'per_piece'
                                    ? this.selectedService.price
                                    : '',
                            });
                        },
                        removeItem(index) { if (this.items.length > 1) this.items.splice(index, 1); }
                    }">
                    @csrf @method('PUT')

                    {{-- Customer --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
                        <select name="customer_id" required
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('customer_id') border-red-300 @enderror">
                            <option value="">— Select Customer —</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $order->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}{{ $customer->phone ? ' ('.$customer->phone.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Service & Weight --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Service</label>
                            <select name="service_id" x-model="serviceId"
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— No Service —</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->formatted_price }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="selectedService && selectedService.price_type === 'per_kilo'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                            <input type="number" name="weight" x-model="weight" min="0" step="0.01"
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <template x-if="selectedService">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600">
                            <p x-show="selectedService.price_type === 'per_kilo'">This order total is based on the recorded weight plus any priced add-ons below.</p>
                            <p x-show="selectedService.price_type === 'per_load'">This service adds one fixed per-load charge plus any priced add-ons below.</p>
                            <p x-show="selectedService.price_type === 'flat'">This service adds one flat-rate charge plus any priced add-ons below.</p>
                            <p x-show="selectedService.price_type === 'per_piece'">Per-piece pricing is active. Each item line uses the service price by default unless you enter a custom line price.</p>
                            <p class="mt-2 text-sm text-gray-500">Service cost: <span class="font-medium" x-text="'₱' + serviceBaseTotal.toFixed(2)"></span></p>
                        </div>
                    </template>

                    {{-- Status & Due Date --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                            <select name="status" required
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ old('status', $order->status) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                            <input type="date" name="due_date" value="{{ old('due_date', $order->due_date?->format('Y-m-d')) }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    {{-- Items --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Laundry Items</label>
                            <button type="button" @click="addItem()"
                                class="inline-flex items-center gap-1 text-xs font-medium {{ $theme['nav_active_text'] }} hover:underline">
                                + Add Item
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(item, index) in items" :key="index">
                                <div class="grid grid-cols-12 gap-2 items-center">
                                    <div class="col-span-6">
                                        <input type="text" :name="`items[${index}][name]`" x-model="item.name" placeholder="Item"
                                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="col-span-2">
                                        <input type="number" :name="`items[${index}][qty]`" x-model="item.qty" min="1" placeholder="Qty"
                                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="col-span-3">
                                        <input type="number" :name="`items[${index}][price]`" x-model="item.price" min="0" step="0.01" :placeholder="selectedService && selectedService.price_type === 'per_piece' ? 'Default piece price' : 'Price (₱)'"
                                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="col-span-1 text-center">
                                        <button type="button" @click="removeItem(index)"
                                            class="text-red-400 hover:text-red-600 disabled:opacity-30" :disabled="items.length === 1">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Total --}}
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Total Amount</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">₱</span>
                            <input type="number" name="total_amount" :value="total.toFixed(2)"
                                min="0" step="0.01" required readonly
                                class="w-32 rounded-md border-gray-300 shadow-sm text-sm text-right font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $order->notes) }}</textarea>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-5 py-2 text-sm font-medium text-white shadow-sm transition">
                            Update Order
                        </button>
                        <a href="{{ route('tenant.orders.show', $order) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-tenant-layout>



