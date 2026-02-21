<x-mail::message>
# Order Ready for Pickup! 🎉

Hi {{ $customerName }},

Great news! Your order **#{{ $orderNumber }}** is ready for pickup!

**Service:** {{ $serviceName }}
**Total Amount:** ₱{{ $totalAmount }}
@if (!$isPaid)
**Payment Status:** Unpaid
@else
**Payment Status:** Paid ✓
@endif

<x-mail::button :url="route('tenant.portal.show', ['order' => $order->id])">
View Order Details
</x-mail::button>

Please pick up your order at your earliest convenience.

Best regards,  
The Laundry Shop Team
</x-mail::message>
