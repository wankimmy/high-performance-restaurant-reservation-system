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

                <div class="mt-8 text-center">
                    <a href="/" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition duration-150">
                        Make Another Reservation
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

