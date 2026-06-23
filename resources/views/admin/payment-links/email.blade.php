@component('mail::message')
# Payment Link

Dear Customer,

You have a new payment request. Please find the details below:

- **Merchant:** {{ $paymentLink->merchant ? $paymentLink->merchant->name : '' }}
- **Amount:** {{ $paymentLink->amount }} {{ $paymentLink->currency }}
- **Status:** {{ ucfirst($paymentLink->status) }}
@if($paymentLink->scheduled_date)
- **Scheduled Date:** {{ $paymentLink->scheduled_date }}
@endif

@component('mail::button', ['url' => $paymentLink->link])
Pay Now
@endcomponent

Thank you for your business!
@endcomponent
