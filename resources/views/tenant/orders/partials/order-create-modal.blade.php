<x-modal name="order-create-modal" :show="$showOrderCreateModal" maxWidth="2xl" focusable>
    <form method="POST" action="{{ route('tenant.orders.store') }}" class="p-6"
        x-data="{
            serviceId: '{{ old('service_id', '') }}',
            weight: {{ old('weight', 0) }},
            services: {{ Js::from($services) }},
            items: {{ Js::from(old('items', [['name' => '', 'qty' => 1, 'price' => '']])) }},
            get selectedService() {
                return this.services.find(s => s.id == this.serviceId);
            },
            get serviceBaseTotal() {
                if (!this.selectedService) return 0;
                if (this.selectedService.price_type === 'per_kilo') {
                    return parseFloat(this.selectedService.price || 0) * parseFloat(this.weight || 0);
                }
                if (this.selectedService.price_type === 'per_piece') {
                    return 0;
                }
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
        @csrf
        <input type="hidden" name="form_context" value="order-create">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Orders</p>
                <h3 class="mt-2 text-lg font-semibold text-gray-900">Create New Order</h3>
                <p class="mt-1 text-sm text-gray-500">Record a new laundry order for your customer.</p>
            </div>

            <button type="button" x-on:click="$dispatch('close')" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-6 space-y-5 max-h-[70vh] overflow-y-auto pr-1">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
                <select name="customer_id" required
                    class="block w-full rounded-md {{ $errors->has('customer_id') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">- Select Customer -</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', request('customer_id')) == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}{{ $customer->phone ? ' ('.$customer->phone.')' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <a href="{{ route('tenant.customers.index', ['create' => 1]) }}" class="mt-1 inline-block text-xs {{ $theme['nav_active_text'] }} hover:underline">+ Add new customer</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Service</label>
                    <select name="service_id" x-model="serviceId"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">- No Service -</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->formatted_price }})</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="selectedService && selectedService.price_type === 'per_kilo'" x-transition>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg) <span class="text-red-500">*</span></label>
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
                </div>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" required
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" {{ old('status', 'received') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Additional Items</label>
                    <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-1 text-xs font-medium {{ $theme['nav_active_text'] }} hover:underline">
                        + Add Item
                    </button>
                </div>

                <div class="space-y-2">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-12 gap-2 items-center">
                            <div class="col-span-6">
                                <input type="text" :name="`items[${index}][name]`" x-model="item.name"
                                    placeholder="Item (e.g. Shirt, Pants)"
                                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="col-span-2">
                                <input type="number" :name="`items[${index}][qty]`" x-model="item.qty" min="1"
                                    placeholder="Qty"
                                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="col-span-3">
                                <input type="number" :name="`items[${index}][price]`" x-model="item.price" min="0" step="0.01"
                                    :placeholder="selectedService && selectedService.price_type === 'per_piece' ? 'Default piece price' : 'Price (Php)'"
                                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="col-span-1 text-center">
                                <button type="button" @click="removeItem(index)"
                                    class="text-red-400 hover:text-red-600 disabled:opacity-30"
                                    :disabled="items.length === 1">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                <span class="text-sm font-medium text-gray-700">Total Amount</span>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Php</span>
                    <input type="number" name="total_amount" :value="total.toFixed(2)"
                        min="0" step="0.01" required readonly
                        class="w-32 rounded-md border-gray-300 shadow-sm text-sm text-right font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2"
                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition">
                Create Order
            </button>
        </div>
    </form>
</x-modal>
