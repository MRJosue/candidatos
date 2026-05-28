<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Tarifas</p>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Precios actuales</h2>
        </div>
    </x-slot>

    <div class="app-dashboard py-5">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="rounded bg-white shadow-sm ring-1 ring-gray-100">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="font-semibold text-gray-900">Planes</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="text-left text-gray-900">
                                <th class="px-4 py-3 font-semibold">Plan</th>
                                <th class="px-4 py-3 font-semibold">Precio sin IVA ajustado</th>
                                <th class="px-4 py-3 font-semibold">Total con IVA</th>
                                <th class="px-4 py-3 font-semibold">CVs incluidos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">Basico</td>
                                <td class="px-4 py-3">$4,151</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">$4,815</td>
                                <td class="px-4 py-3">600 CV/mes</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">Medio volumen</td>
                                <td class="px-4 py-3">$6,227</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">$7,223</td>
                                <td class="px-4 py-3">1,500 CV/mes</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">Alto volumen</td>
                                <td class="px-4 py-3">$9,341</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">$10,835</td>
                                <td class="px-4 py-3">3,000 CV/mes</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded bg-white shadow-sm ring-1 ring-gray-100">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="font-semibold text-gray-900">Consumos adicionales</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="text-left text-gray-900">
                                <th class="px-4 py-3 font-semibold">Concepto</th>
                                <th class="px-4 py-3 font-semibold">Costo sin IVA</th>
                                <th class="px-4 py-3 font-semibold">Total con IVA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">CV convertido/procesado</td>
                                <td class="px-4 py-3">$2.00</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">$2.32</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">Traducci&oacute;n de CV</td>
                                <td class="px-4 py-3">$1.00</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">$1.16</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">CV procesado + traducci&oacute;n</td>
                                <td class="px-4 py-3">$3.00</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">$3.48</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded bg-white shadow-sm ring-1 ring-gray-100">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="font-semibold text-gray-900">Servicios por hora</h3>
                </div>

                <div class="px-5 py-5">
                    <ul class="list-disc space-y-3 ps-5 text-sm text-gray-700">
                        <li>Soporte: <span class="font-semibold text-gray-900">$350 + IVA por hora</span></li>
                        <li>Desarrollo/customizaciones: <span class="font-semibold text-gray-900">$850 + IVA por hora</span></li>
                    </ul>
                </div>
            </div>

            <div class="rounded bg-white shadow-sm ring-1 ring-gray-100">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="font-semibold text-gray-900">Servicios iniciales</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="text-left text-gray-900">
                                <th class="px-4 py-3 font-semibold">Concepto</th>
                                <th class="px-4 py-3 font-semibold">Precio sin IVA</th>
                                <th class="px-4 py-3 font-semibold">Total con IVA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            <tr>
                                <td class="px-4 py-3">Instalaci&oacute;n inicial</td>
                                <td class="px-4 py-3">$2,500</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">$2,900</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
