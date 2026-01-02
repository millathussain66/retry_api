<x-app-layout>



    
    <script>
        $(document).ready(function() {
            $('#myApiCallButton').click(function() {
                $('#loadingSpinner').show();
                let csrfToken = '{{ csrf_token() }}';

                $.ajax({
                    url: '{{ route("api.createCreditCard") }}',
                    method: 'POST',
                    data: {
                        _token           : csrfToken,
                        lead_id          : 1,
                        file_info_id     : 1001,
                        candidate_info_id: 1,
                        file_sl_no       : 'CA-000000001',
                    },
                    success: function(response) {
                       // window.location.reload();

                    },
                    error: function(xhr, status, error) {
                        // alert('An error occurred while making API calls.');
                        window.location.reload();
                    },
                    complete: function() {
                        Swal.fire({
                            title: "All API calls have been dispatched!",
                            icon: "success",
                            draggable: true,
                            position: 'top-end',
                        });
                        $('#loadingSpinner').hide();
                    }
                });
               




            });
        });
    </script>

<div class="w-full px-4 py-6 bg-gray-50 min-h-screen">
    <div class="max-w-[1800px] mx-auto">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">API Dashboard</h1>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button id="myApiCallButton"
                        class="bg-blue-600 hover:bg-blue-700 transition text-white font-semibold py-2 px-6 rounded-lg shadow">
                        Run API Load Test
                    </button>
                    
                    <img id="loadingSpinner"
                        src="{{ asset('public/assets/spinner.gif') }}"
                        class="w-8 hidden"
                        alt="Loading">
                </div>
                <p class="text-sm text-gray-600">
                    Clicking triggers 20+ background API calls to simulate heavy server load
                </p>
            </div>
        </div>

        <!-- Main Grid - 4 Columns -->
        <div class="grid grid-cols- lg:grid-cols-4 gap-6">

            <!-- SECTION 1: API Queue (Recent Activity) -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
                <div class="px-6 py-4 border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">
                            API Call Queue
                        </h3>
                        <span class="text-sm text-gray-500">Recent activity</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-xs uppercase text-gray-500">
                                <th class="px-6 py-3">SL</th>
                                <th class="px-6 py-3">API</th>
                                <th class="px-6 py-3">File No</th>
                                <th class="px-6 py-3">Time</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse (DB::table('retry_api_queue')->orderBy('id','ASC')->limit(10)->get() as $index => $record)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-medium">#{{ $index + 1 }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium">{{ $record->api_name }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $record->api_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-medium">{{ $record->file_sl_no }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm">{{ \Carbon\Carbon::parse($record->created_at)->format('h:i A') }}</div>
                                        <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($record->created_at)->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full mb-1
                                                {{ $record->success_status
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-red-100 text-red-700' }}">
                                                {{ $record->success_status ? '✓ Success' : '✗ Failed' }}
                                            </span>
                                            @if($record->success_status)
                                                <span class="text-xs text-gray-500">File: {{ $record->file_sl_no }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500">
                                        🚫 No queue data found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

         
            <div class="lg:col-span-2 bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">API Details</h3>
                    <p class="text-sm text-gray-500">Type & URLs</p>
                </div>


                
                <div class="overflow-x-auto max-h-[500px]">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 bg-gray-50">
                            <tr class="text-xs uppercase text-gray-500">
                                <th class="px-4 py-3">API ID</th>
                                <th class="px-4 py-3">API Name</th>
                                <th class="px-4 py-3">Sequence</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">URL</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse (DB::table('card_api_list')->orderBy('id','asc')->get() as $index => $api)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $api->id }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $api->api_name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $api->api_calling_sequence }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 text-xs rounded-full font-semibold
                                            {{ $api->api_type === 'ETOB'
                                                ? 'bg-purple-100 text-purple-700'
                                                : 'bg-blue-100 text-blue-700' }}">
                                            {{ $api->api_type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-blue-600 truncate max-w-[180px]" title="{{ $api->api_url }}">
                                            {{ $api->api_url }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Created: {{ \Carbon\Carbon::parse($api->created_at)->format('d M') }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-gray-500">
                                        No API details
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>




            </div>

            <!-- SECTION 4: Server Stats -->
            <div class="lg:col-span-4 bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Server Monitor</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">
                    <!-- Server 1 -->
                    <div class="bg-gray-50 rounded-lg p-4 border">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-gray-700">Server 1</h4>
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">Online</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">CPU Usage</span>
                                <span class="font-medium">42%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Memory</span>
                                <span class="font-medium">68%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">API Calls</span>
                                <span class="font-medium">1,234</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Server 2 -->
                    <div class="bg-gray-50 rounded-lg p-4 border">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-gray-700">Server 2</h4>
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">Online</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">CPU Usage</span>
                                <span class="font-medium">38%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Memory</span>
                                <span class="font-medium">72%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">API Calls</span>
                                <span class="font-medium">987</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Server 3 -->
                    <div class="bg-gray-50 rounded-lg p-4 border">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-gray-700">Server 3</h4>
                            <span class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded-full">Offline</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">CPU Usage</span>
                                <span class="font-medium">0%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Memory</span>
                                <span class="font-medium">0%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">API Calls</span>
                                <span class="font-medium">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Server 4 -->
                    <div class="bg-gray-50 rounded-lg p-4 border">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-gray-700">Server 4</h4>
                            <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full">Maintenance</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">CPU Usage</span>
                                <span class="font-medium">15%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Memory</span>
                                <span class="font-medium">22%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">API Calls</span>
                                <span class="font-medium">45</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</x-app-layout>
