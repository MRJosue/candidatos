<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Nuevo CV</h2></x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm">
        <form method="POST" action="{{ route('cv.store') }}" class="space-y-4">@include('cv._form')</form>
    </div></div>
</x-app-layout>
