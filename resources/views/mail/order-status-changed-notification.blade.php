<x-mail::message>
# Order Status Update

Hi {{ $customerName }},

Your order **#{{ $orderNumber }}** status has been updated!

**New Status:** {{ $newStatus }}

**Service:** {{ $serviceName }}
@if ($dueDate)
**Due Date:** {{ $dueDate }}
@endif

<x-mail::button :url="$actionUrl">
{{ $actionLabel }}
</x-mail::button>

Thank you for choosing our service!

Best regards,  
The Laundry Shop Team
</x-mail::message>
