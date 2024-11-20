<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $statistic->title }}</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Özel CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <!-- AlpineJS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50" x-data="{ isOpen: false, isCategoryModalOpen: false }">
    <!-- Navbar -->
    @include('layouts.header', ['title' => $title,'menuItems' => $menuItems])

    <!-- Main Content -->
    <main class="pt-20 w-full">
        <!-- Map Section -->
        <section id="map" class="w-full md:max-w-7xl md:mx-auto md:px-4">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-2 h-8 bg-indigo-600 rounded-full"></div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $statistic->title }}</h1> 
            </div>
            <p>{!! $statistic->content !!}</p><br><br>
        </section>
        
        <section id="map">
            <h3 class="bg-white w-full px-4 py-2 shadow">{{ $statistic->title }} haritası</h3>
            <div class="map-placeholder">
                @includeIf('maps.' . $statistic->category->data_source . '_map')
            </div>
        </section>
        <br><br>

        <!-- Statistics Section -->
        @if($statistic->numeric_type === 'percentage')
            <!-- Bar Chart Section -->
            <section id="percentage-bar-chart">
                <h4 class="bg-white w-full px-4 py-2 shadow">Yüzdelik Değerler (Bar Grafiği)</h4>
                <div class="w-full md:max-w-7xl md:mx-auto md:px-4 h-96">
                    <canvas id="percentageBarChart" class="w-full h-full"></canvas>
                </div>
            </section>
            <br><br>
            <!-- Pie Chart Section -->
            <section id="percentage-stats">
                <h4 class="bg-white w-full px-4 py-2 shadow">Yüzdelik Değerler (Daire Grafiği)</h4>
                <div class="w-full md:max-w-7xl md:mx-auto md:px-4 h-96">
                    <canvas id="percentageChart" class="w-full h-full"></canvas>
                </div>
            </section>
			<br><br>
			
          <div class="w-full mx-auto lg:max-w-6xl lg:p-4 md:p-2">
                <h2 class="bg-white w-full px-4 py-2 shadow">{{ $statistic->title }}</h2>
                <!-- Üst Toolbar -->
                <div class="bg-white p-4 rounded-t-lg border-b flex justify-between items-center space-x-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="search" id="table-search1" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Ara...">
                    </div>
                    <button id="downloadBtn1" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span>İndir</span>
                    </button>
                </div>

                <!-- Tablo Container -->
<div class="relative overflow-auto border rounded-b-lg bg-white" style="max-height: 400px;">
    <table class="w-full text-sm text-left" id="sortable-table1">
        <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10">
            <tr>
                <th class="sticky left-0 bg-gray-100 px-4 py-3 border-b border-r cursor-pointer hover:bg-gray-200" data-sort="id">
                    Ülke
                    <span class="sort-icon ml-1">↕</span>
                </th>
                @foreach($statistic->items->pluck('year')->unique()->sort() as $year)
                    <th class="px-4 py-3 border-b border-r cursor-pointer hover:bg-gray-200" data-sort="value">
                        {{ $year }} (%)
                        <span class="sort-icon ml-1">↕</span>
                    </th>    
                @endforeach                                
            </tr>
        </thead>
        <tbody class="divide-y">
            @php
                // En son yıla göre sıralama yap
                $latestYear = $statistic->items->pluck('year')->max();
                $sortedItems = $statistic->items
                    ->groupBy(function($item) {
                        return $item->country_id ? $item->country->name : ($item->custom_name ?? 'Özel İsim');
                    })
                    ->sortByDesc(function($items) use ($latestYear) {
                        return $items->where('year', $latestYear)->first()->value ?? 0;
                    });
            @endphp

            @foreach($sortedItems as $name => $items)
                <tr class="hover:bg-gray-50">
                    <td class="sticky left-0 bg-white px-4 py-2 border-r whitespace-nowrap">
                        {{ $name }}
                    </td>
                    @foreach($statistic->items->pluck('year')->unique()->sort() as $year)
                        @php
                            $item = $items->firstWhere('year', $year);
                            $percentage = $item ? $item->value : 0;
                        @endphp
                        <td class="px-4 py-2 border-r whitespace-nowrap">{{ $percentage }}%</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
        @else
           <!-- Diğer istatistik türleri için mevcut kod -->
            <div class="w-full mx-auto lg:max-w-6xl lg:p-4 md:p-2">
                <h2 class="bg-white w-full px-4 py-2 shadow">{{ $statistic->title }}</h2>
                <!-- Üst Toolbar -->
                <div class="bg-white p-4 rounded-t-lg border-b flex justify-between items-center space-x-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="search" id="table-search1" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Ara...">
                    </div>
                    <button id="downloadBtn1" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span>İndir</span>
                    </button>
                </div>

                <!-- Tablo Container -->
                <div class="relative overflow-auto border rounded-b-lg bg-white" style="max-height: 400px;">
                    <table class="w-full text-sm text-left" id="sortable-table1">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10">
                            <tr>
                                <th class="sticky left-0 bg-gray-100 px-4 py-3 border-b border-r cursor-pointer hover:bg-gray-200" data-sort="id">
                                    Ülke
                                    <span class="sort-icon ml-1">↕</span>
                                </th>
                                <th class="px-4 py-3 border-b border-r cursor-pointer hover:bg-gray-200" data-sort="value">
                                    Değer {{ $statistic->unit_type }} ( {{ $statistic->primary_year }})
                                    <span class="sort-icon ml-1">↕</span>
                                </th>					
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($statistic->items->where('year', $statistic->primary_year) as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="sticky left-0 bg-white px-4 py-2 border-r whitespace-nowrap">
                                        {{ $item->country_id ? $item->country->name : ($item->custom_name ?? 'Özel İsim') }}
                                    </td>
                                    <td class="px-4 py-2 border-r whitespace-nowrap">{{ $item->value }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif	
        <br><br>
        <!-- Description Section -->
        <section id="description">
            <h4 class="bg-white w-full px-4 py-2 shadow">Detaylar</h4>
            <div>
                @foreach($statistic->others as $other)
                    <p>{{ $other->message }}</p>
                @endforeach
            </div>
        </section>
        <br><br>
        
        <!-- Yıllara Göre Karşılaştırmalı Tablo -->
        @if($statistic->items->pluck('year')->unique()->count() >= 2)
            <div class="w-full mx-auto lg:max-w-6xl lg:p-4 md:p-2">
                <h4 class="bg-white w-full px-4 py-2 shadow">{{ $statistic->title }} - Yıllara Göre Karşılaştırmalı Tablo</h4>
                <!-- Üst Toolbar -->
                <div class="bg-white p-4 rounded-t-lg border-b flex justify-between items-center space-x-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="search" id="table-search2" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Ara...">
                    </div>
                    <button id="downloadBtn2" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span>İndir</span>
                    </button>
                </div>

                <!-- Tablo Container -->
                <div class="relative overflow-auto border rounded-b-lg bg-white max-h-[600px]">
                    <table class="w-full text-sm text-left" id="sortable-table2">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10">
                            <tr>
                                <th class="sticky left-0 bg-gray-100 px-4 py-3 border-b border-r cursor-pointer hover:bg-gray-200 max-w-xs" data-sort="country">
                                    Ülke
                                </th>
                                @foreach($statistic->items->pluck('year')->unique()->sort() as $year)
                                    <th class="px-4 py-3 border-b border-r cursor-pointer hover:bg-gray-200 max-w-xs" data-sort="year_{{$year}}">
                                        {{ $year }} ({{ $statistic->unit_type }})
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($statistic->items->groupBy(function($item) {
                                return $item->country_id ? $item->country->name : ($item->custom_name ?? 'Özel İsim');
                            }) as $name => $items)
                                <tr class="hover:bg-gray-50">
                                    <td class="sticky left-0 bg-white px-4 py-2 border-r whitespace-nowrap">{{ $name }}</td>
                                    @foreach($statistic->items->pluck('year')->unique()->sort() as $year)
                                        @php
                                            $item = $items->firstWhere('year', $year);
                                        @endphp
                                        <td class="px-4 py-2 border-r whitespace-nowrap">{{ $item ? $item->value : '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- FAQ Section -->
        <br><br>
        <section id="faq" class="w-full md:max-w-3xl md:mx-auto md:px-4 py-8">
            <h3 class="bg-white w-full px-4 py-2 shadow">Sık Sorulan Sorular</h3>
            <div class="space-y-4">
                @foreach($statistic->faqs as $faq)
                    <div x-data="{ open: false }" class="border rounded-lg">
                        <!-- Soru başlığı -->
                        <button 
                            @click="open = !open" 
                            class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 focus:outline-none">
                            <h4>{{ $faq->question }}</h4>
                            <!-- Ok ikonu -->
                            <svg 
                                class="w-5 h-5 transition-transform duration-200" 
                                :class="{'rotate-180': open}"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <!-- Cevap bölümü -->
                        <div 
                            x-show="open" 
                            x-transition:enter="transition ease-out duration-200" 
                            x-transition:enter-start="opacity-0 transform -translate-y-2" 
                            x-transition:enter-end="opacity-100 transform translate-y-0" 
                            x-transition:leave="transition ease-in duration-200" 
                            x-transition:leave-start="opacity-100 transform translate-y-0" 
                            x-transition:leave-end="opacity-0 transform -translate-y-2" 
                            class="px-6 py-4 border-t bg-gray-50"
                        >
                            <p class="text-gray-600">{{ $faq->answer }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        <br><br>
        <!-- Sources Section -->
        <section id="sources" class="w-full md:max-w-7xl md:mx-auto md:px-4">
            <h2 class="bg-white w-full px-4 py-2 shadow">Kaynaklar</h2>
            <ul>
                @foreach($statistic->sources as $source)
                    <li><a href="{{ $source->url }}">{{ $source->title }}</a></li>
                @endforeach
            </ul>
        </section>
    </main>
    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold">StatsPro</h3>
                    <p class="mt-4 text-gray-400">
                        Veri analizi ve görselleştirme platformu
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold">Ürün</h4>
                    <ul class="mt-4 space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Özellikler</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Fiyatlandırma</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">API</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold">Şirket</h4>
                    <ul class="mt-4 space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Hakkımızda</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Blog</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Kariyer</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold">İletişim</h4>
                    <ul class="mt-4 space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Destek</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">İletişim</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Docs</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-12 border-t border-gray-800 pt-8 flex justify-between items-center">
                <div class="text-gray-400">
                    © 2024 StatsPro. Tüm hakları saklıdır.
                </div>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-linkedin"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
document.addEventListener('DOMContentLoaded', function() {
    @if($statistic->numeric_type === 'percentage')
        // Pie Grafiği (Pie Chart)
        var ctxPie = document.getElementById('percentageChart').getContext('2d');
        var dataPie = {
            labels: [],
            datasets: [{
                label: 'Yüzde Değerleri',
                data: [],
                backgroundColor: [
                    '#556b2f', '#8b4513', '#4682b4', '#6a5acd', '#708090', '#9acd32', '#cd5c5c', 
                    '#ffa07a', '#20b2aa', '#6495ed', '#f08080', '#9370db', '#3cb371', '#b8860b',
                    '#2f4f4f', '#8fbc8f', '#483d8b', '#bc8f8f', '#4682b4', '#5f9ea0', '#6b8e23', 
                    '#7b68ee', '#556b2f', '#8a2be2', '#a52a2a', '#deb887', '#5d3fd3', '#ff6347',
                    '#8b0000', '#ff8c00', '#8a2be2', '#7f7f7f', '#4b0082', '#9932cc', '#800000'
                ],
                borderColor: [],
                borderWidth: 1
            }]
        };

        @foreach($statistic->items->groupBy(function($item) {
            return $item->country_id ? $item->country->name : ($item->custom_name ?? 'Özel İsim');
        })->sortByDesc(function($group) use ($statistic) {
            return $group->where('year', $statistic->primary_year)->first()->value ?? 0;
        }) as $name => $items)
            @php
                $totalValue = $items->sum('value');
            @endphp
            dataPie.labels.push("{{ $name }}");
            dataPie.datasets[0].data.push({{ $totalValue }});
            dataPie.datasets[0].borderColor.push('#ffffff');
        @endforeach

        var percentageChart = new Chart(ctxPie, {
            type: 'pie',
            data: dataPie,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
            legend: {
                position: 'top',
                labels: {
                    boxWidth: 15, // Legend kutu genişliği
                    padding: 10,  // Legend'lar arası boşluk
                    font: {
                        size: 11   // Font boyutu
                    },
                    maxWidth: 150 // Maksimum genişlik
                },
                maxHeight: 200  // Legend alanının maksimum yüksekliği
            },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                var total = dataPie.datasets[0].data.reduce((a, b) => a + b, 0);
                                var percentage = ((tooltipItem.raw / total) * 100).toFixed(2);
                                return tooltipItem.label + ': ' + percentage + '%';
                            }
                        }
                    }
                }
            }
        });

        // Bar Chart için yeni kod
        var ctxBar = document.getElementById('percentageBarChart').getContext('2d');

        @php
            $countries = $statistic->items->groupBy(function($item) {
                return $item->country_id ? $item->country->name : ($item->custom_name ?? 'Özel İsim');
            })->sortByDesc(function($group) use ($statistic) {
                return $group->where('year', $statistic->primary_year)->first()->value ?? 0;
            });
        @endphp

        var labelsBar = [];
        var valuesBar = [];
        var colorsBarSingle = [
            @php
                $colorsBar = [
                    '#4dc9f6', '#f67019', '#f53794', '#537bc4', '#acc236', '#166a8f',
                    '#00a950', '#58595b', '#8549ba', '#e6194b', '#3cb44b', '#ffe119',
                    '#ff7f50', '#6a5acd', '#40e0d0', '#ff6347', '#4682b4', '#daa520',
                    '#32cd32', '#ff4500', '#6b8e23', '#7b68ee', '#8b008b', '#20b2aa',
                    '#ff1493', '#2e8b57', '#1e90ff', '#ff69b4', '#9932cc', '#8fbc8f',
                    '#ff8c00', '#800080', '#00ced1', '#ba55d3', '#cd5c5c', '#daa520'
                ];
            @endphp
            @foreach($countries as $name => $items)
                "{{ $colorsBar[$loop->index % count($colorsBar)] }}",
            @endforeach
        ];

        @foreach($countries as $name => $items)
            @php
                $value = $items->where('year', $statistic->primary_year)->first()->value ?? 0;
            @endphp
            labelsBar.push("{{ $name }}");
            valuesBar.push({{ $value }});
        @endforeach

        var percentageBarChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: labelsBar,
                datasets: [{
                    label: '{{ $statistic->title }}',
                    data: valuesBar,
                    backgroundColor: colorsBarSingle,
                    borderColor: '#ffffff',
                    borderWidth: 1,
                    maxBarThickness: 30
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        title: {
                            display: true,
                            text: '{{ $statistic->unit_type }} (%)'
                        }
                    },
                    y: {
                        title: {
                            display: false // Ülke başlığını kaldır
                        },
                        ticks: {
                            autoSkip: false,
                            padding: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.x + '%';
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        left: 20,
                        right: 50 // Değerlerin sığması için sağ padding
                    }
                }
            },
            plugins: [{
                afterDraw: function(chart) {
                    var ctx = chart.ctx;
                    var xAxis = chart.scales.x;
                    var yAxis = chart.scales.y;
                    
                    chart.data.datasets[0].data.forEach(function(value, index) {
                        var x = xAxis.getPixelForValue(value);
                        var y = yAxis.getPixelForValue(chart.data.labels[index]);
                        
                        ctx.fillStyle = '#666';
                        ctx.font = '12px Arial';
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'middle';
                        
                        ctx.fillText(
                            value + '%',
                            x + 5,
                            y
                        );
                    });
                }
            }]
        });
        // Debug için konsol logları
        console.log('Pie Chart Data:', dataPie);
        console.log('Bar Chart Data:', {
            labels: @json($statistic->items->groupBy(function($item) {
                return $item->country_id ? $item->country->name : ($item->custom_name ?? 'Özel İsim');
            })->sortByDesc(function($group) use ($statistic) {
                return $group->where('year', $statistic->primary_year)->first()->value ?? 0;
            })->keys()),
            data: @json($statistic->items->groupBy(function($item) {
                return $item->country_id ? $item->country->name : ($item->custom_name ?? 'Özel İsim');
            })->sortByDesc(function($group) use ($statistic) {
                return $group->where('year', $statistic->primary_year)->first()->value ?? 0;
            })->map(function($items) use ($statistic) {
                return $items->where('year', $statistic->primary_year)->first()->value ?? 0;
            }))
        });
    @endif
});
</script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const statisticTitle = "{{ $statistic->title }}";

        // Tek indirme fonksiyonu
        function downloadTableAsCSV(tableId, tableType) {
            const table = document.getElementById(tableId);
            if (!table) return;

            let csv = [];
            csv.push(`\uFEFF"${statisticTitle.toUpperCase()}"`);  // Başlığı büyük harflerle ekler ve UTF-8 BOM'u başa ekler

            // Başlıkları Al ve her başlığı kendi hücresine koy
            const headers = Array.from(table.querySelectorAll('th')).map(th => {
                const headerText = th.textContent.trim();
                return `"${headerText.replace(/↕/g, '').trim()}"`;  // Her başlığı tırnak içine al
            });
            csv.push(headers.join(';'));  // Başlıkları noktalı virgülle ayırarak ekler

            // Tablo Verilerini Al ve her veriyi kendi hücresine yerleştir
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('td')).map(cell => {
                    let text = cell.textContent.trim();
                    return `"${text}"`;  // Her hücre içeriğini çift tırnak içine al
                });
                csv.push(cells.join(';'));  // Her satırı noktalı virgülle ayırarak ekler
            });

            // Dosyanın sonuna geçerli sayfa URL'sini yeni bir satır olarak ekle
            const currentUrl = window.location.href;  // Geçerli sayfanın URL'sini al
            csv.push(`\n"Kaynak URL:"; "${currentUrl}"`); // Yeni bir satırda noktalı virgülle ayırarak ekler

            // CSV Dosyasını İndir
            const csvContent = csv.join('\n');  // Satırları alt alta yerleştir
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `${statisticTitle}-${tableType}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Arama Fonksiyonu
        function setupSearch(searchId, tableId) {
            const searchInput = document.getElementById(searchId);
            if (!searchInput) return;

            searchInput.addEventListener('keyup', function() {
                const table = document.getElementById(tableId);
                if (!table) return;

                const searchText = this.value.toLowerCase();
                const tableRows = table.querySelectorAll('tbody tr');
                
                tableRows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    row.style.display = rowText.includes(searchText) ? '' : 'none';
                });
            });
        }

        // Sıralama Fonksiyonu
        function setupSorting(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;

            table.querySelectorAll('th[data-sort]').forEach(th => {
                th.addEventListener('click', () => {
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const columnIndex = Array.from(th.parentElement.children).indexOf(th);
                    
                    const isAscending = !th.classList.contains('sort-asc');
                    
                    // Tüm başlıklardan sıralama sınıflarını kaldır
                    table.querySelectorAll('th').forEach(header => {
                        header.classList.remove('sort-asc', 'sort-desc');
                    });
                    
                    // Tıklanan başlığa sıralama sınıfını ekle
                    th.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
                    
                    // Satırları sıralama
                    rows.sort((a, b) => {
                        const aValue = a.children[columnIndex].textContent.trim();
                        const bValue = b.children[columnIndex].textContent.trim();
                        
                        // Sayısal değerleri karşılaştır
                        if (!isNaN(aValue) && !isNaN(bValue)) {
                            return isAscending 
                                ? parseFloat(aValue) - parseFloat(bValue)
                                : parseFloat(bValue) - parseFloat(aValue);
                        }
                        
                        // Metinsel değerleri karşılaştır
                        return isAscending
                            ? aValue.localeCompare(bValue)
                            : bValue.localeCompare(aValue);
                    });
                    
                    // Sıralanmış satırları tabloya ekle
                    rows.forEach(row => tbody.appendChild(row));
                });
            });
        }

        // Event Listener'ları ekle
        const downloadBtn1 = document.getElementById('downloadBtn1');
        const downloadBtn2 = document.getElementById('downloadBtn2');

        if (downloadBtn1) {
            downloadBtn1.addEventListener('click', () => downloadTableAsCSV('sortable-table1', 'tablo1'));
        }
        if (downloadBtn2) {
            downloadBtn2.addEventListener('click', () => downloadTableAsCSV('sortable-table2', 'tablo2'));
        }

        // Arama ve sıralama fonksiyonlarını çağır
        setupSearch('table-search1', 'sortable-table1');
        setupSearch('table-search2', 'sortable-table2');
        setupSorting('sortable-table1');
        setupSorting('sortable-table2');
    });
    </script>
@stack('scripts')
</body>
</html>
