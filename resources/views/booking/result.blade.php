@extends('layouts.app')

@section('title', 'Reservation ' . ($status === 'confirmed' ? 'Confirmed' : 'Failed'))

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            @if($status === 'confirmed')
                <div class="bg-gradient-to-r from-green-600 to-green-500 px-6 py-8 text-center">
                    <div class="mb-4">
                        <svg class="mx-auto h-16 w-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-white">Reservation Confirmed!</h2>
                    <p class="mt-2 text-green-100">Your reservation has been successfully confirmed</p>
                </div>
            @else
                <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-8 text-center">
                    <div class="mb-4">
                        <svg class="mx-auto h-16 w-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-white">Reservation Failed</h2>
                    <p class="mt-2 text-red-100">{{ $message ?? 'Something went wrong with your reservation' }}</p>
                </div>
            @endif
            
            <div class="p-8">
                @if($status === 'confirmed' && isset($reservation))
                    <div class="space-y-6">
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Reservation Details</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Reservation ID:</span>
                                    <span class="font-semibold text-gray-900">#{{ $reservation->id }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Name:</span>
                                    <span class="font-semibold text-gray-900">{{ $reservation->customer_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Email:</span>
                                    <span class="font-semibold text-gray-900">{{ $reservation->customer_email }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Phone:</span>
                                    <span class="font-semibold text-gray-900">{{ $reservation->customer_phone }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Table:</span>
                                    <span class="font-semibold text-gray-900">{{ $reservation->table->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Date:</span>
                                    <span class="font-semibold text-gray-900">{{ $reservation->reservation_date->format('F d, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Time:</span>
                                    <span class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Guests:</span>
                                    <span class="font-semibold text-gray-900">{{ $reservation->pax }} {{ $reservation->pax == 1 ? 'person' : 'people' }}</span>
                                </div>
                                @if($reservation->notes)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Notes:</span>
                                    <span class="font-semibold text-gray-900">{{ $reservation->notes }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-sm text-blue-800">
                                <strong>Note:</strong> A confirmation email and WhatsApp message have been sent to you with these details.
                            </p>
                        </div>
                    </div>
                @endif

                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center items-center">
                    @if($status === 'confirmed' && isset($reservation))
                        <button onclick="printReceipt()" 
                                class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Save Reservation
                        </button>
                    @endif
                    <a href="/" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                        Make Another Reservation
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Receipt for Printing -->
@if($status === 'confirmed' && isset($reservation))
<div id="receipt-content" class="hidden">
    <div style="max-width: 800px; margin: 0 auto; padding: 40px; font-family: Arial, sans-serif;">
        <div style="text-align: center; margin-bottom: 30px; border-bottom: 3px solid #4f46e5; padding-bottom: 20px;">
            <h1 style="color: #4f46e5; font-size: 32px; margin: 0;">{{ config('app.name', 'Restaurant Reservation') }}</h1>
            <p style="color: #666; margin-top: 10px; font-size: 14px;">Reservation Receipt</p>
        </div>
        
        <div style="background: #f9fafb; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
            <h2 style="color: #111827; font-size: 20px; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">Reservation Details</h2>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; width: 40%; font-size: 14px;">Reservation ID:</td>
                    <td style="padding: 12px 0; font-weight: bold; color: #111827; font-size: 14px;">#{{ $reservation->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-size: 14px;">Customer Name:</td>
                    <td style="padding: 12px 0; font-weight: bold; color: #111827; font-size: 14px;">{{ $reservation->customer_name }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-size: 14px;">Email:</td>
                    <td style="padding: 12px 0; font-weight: bold; color: #111827; font-size: 14px;">{{ $reservation->customer_email }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-size: 14px;">Phone:</td>
                    <td style="padding: 12px 0; font-weight: bold; color: #111827; font-size: 14px;">{{ $reservation->customer_phone }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-size: 14px;">Table:</td>
                    <td style="padding: 12px 0; font-weight: bold; color: #111827; font-size: 14px;">{{ $reservation->table->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-size: 14px;">Date:</td>
                    <td style="padding: 12px 0; font-weight: bold; color: #111827; font-size: 14px;">{{ $reservation->reservation_date->format('F d, Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-size: 14px;">Time:</td>
                    <td style="padding: 12px 0; font-weight: bold; color: #111827; font-size: 14px;">{{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-size: 14px;">Number of Guests:</td>
                    <td style="padding: 12px 0; font-weight: bold; color: #111827; font-size: 14px;">{{ $reservation->pax }} {{ $reservation->pax == 1 ? 'person' : 'people' }}</td>
                </tr>
                @if($reservation->deposit_amount > 0)
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-size: 14px;">Deposit Amount:</td>
                    <td style="padding: 12px 0; font-weight: bold; color: #111827; font-size: 14px;">RM {{ number_format($reservation->deposit_amount, 2) }}</td>
                </tr>
                @endif
                @if($reservation->notes)
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-size: 14px; vertical-align: top;">Special Notes:</td>
                    <td style="padding: 12px 0; font-weight: bold; color: #111827; font-size: 14px;">{{ $reservation->notes }}</td>
                </tr>
                @endif
            </table>
        </div>
        
        <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; margin-bottom: 25px; border-radius: 4px;">
            <p style="margin: 0; color: #1e40af; font-size: 13px; line-height: 1.6;">
                <strong>Important:</strong> Please arrive on time for your reservation. If you need to cancel or modify your reservation, please contact us as soon as possible.
            </p>
        </div>
        
        <div style="text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <p style="margin: 5px 0;">Thank you for choosing {{ config('app.name', 'our restaurant') }}!</p>
            <p style="margin: 5px 0;">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function printReceipt() {
        const receiptContent = document.getElementById('receipt-content').innerHTML;
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Reservation Receipt #{{ $reservation->id }}</title>
                <style>
                    @media print {
                        @page {
                            margin: 20mm;
                            size: A4;
                        }
                        body {
                            margin: 0;
                            padding: 0;
                        }
                    }
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 0;
                    }
                </style>
            </head>
            <body>
                ${receiptContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        // Wait for content to load, then trigger print dialog
        printWindow.onload = function() {
            setTimeout(() => {
                printWindow.print();
            }, 250);
        };
    }
</script>
@endpush
@endif
@endsection

