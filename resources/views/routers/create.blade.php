{{-- resources/views/routers/create.blade.php --}}

@extends('layouts.app')

@section('header')
    <h2 class="text-2xl font-semibold text-gray-800">Add New MikroTik Router</h2>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- ──────────────── New Router Form Card ──────────────── --}}
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium text-gray-800">New Router Details</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('routers.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    {{-- Router Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Router Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            required
                            placeholder="Office Router"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- IP Address --}}
                    <div>
                        <label for="ip_address" class="block text-sm font-medium text-gray-700">IP Address <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="ip_address"
                            id="ip_address"
                            required
                            placeholder="192.168.88.1"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        @error('ip_address')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- API Port --}}
                    <div>
                        <label for="port" class="block text-sm font-medium text-gray-700">API Port</label>
                        <input
                            type="number"
                            name="port"
                            id="port"
                            value="8728"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        @error('port')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Admin Username --}}
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Admin Username <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            required
                            placeholder="admin"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        @error('username')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    {{-- Location --}}
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                        <input
                            type="text"
                            name="location"
                            id="location"
                            placeholder="Building A, Floor 3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        @error('location')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            placeholder="Main office router"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        ></textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Submit / Cancel Buttons --}}
                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                    <a 
                        href="{{ route('routers.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent 
                               rounded-md font-medium text-white hover:bg-gray-600 focus:outline-none 
                               focus:ring-2 focus:ring-gray-500"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent 
                               rounded-md font-medium text-white hover:bg-indigo-700 focus:outline-none 
                               focus:ring-2 focus:ring-indigo-500"
                    >
                        Save Router
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
