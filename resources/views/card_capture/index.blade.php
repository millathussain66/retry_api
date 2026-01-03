<x-app-layout>
<script>
$(document).ready(function () {

    fetchData();

    function fetchData(page = 1) {
        $('#loadingSpinner').removeClass('hidden');

        $.ajax({
            url: "{{ route('card_capture') }}?page=" + page,
            type: "GET",
            success: function (res) {
                const tbody = $('#cardTable tbody');
                tbody.html('');
                $('#paginationLinks').html('');

                res.data.forEach(item => {
                    tbody.append(`
                        <tr class="text-center border-t">
                            <td class="px-4 py-2 border">${item.id}</td>
                            <td class="px-4 py-2 border"><button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1 px-3 rounded">Card Capture</button></td>
                            <td class="px-4 py-2 border">${item.file_sl_no}</td>
                            <td class="px-4 py-2 border">${item.lead_id}</td>
                            <td class="px-4 py-2 border">${item.candidate_name ?? 'N/A'}</td>
                        </tr>
                    `);
                });

                // pagination buttons
                let pagination = '';
                for (let i = 1; i <= res.last_page; i++) {
                    pagination += `
                        <button 
                            class="px-3 py-1 mx-1 rounded ${i === res.current_page ? 'bg-blue-600 text-white' : 'bg-gray-200'}"
                            onclick="fetchData(${i})">
                            ${i}
                        </button>
                    `;
                }
                $('#paginationLinks').html(pagination);

                $('#loadingSpinner').addClass('hidden');
            }
        });
    }

    window.fetchData = fetchData;
});
</script>


<div class="w-full px-4 py-6 bg-gray-50 min-h-screen">
    <div class="max-w-[1800px] mx-auto">

        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">Card Capture Grid</h1>
            <div class="flex items-center gap-4">
                <button id="myApiCallButton"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow">
                    Add
                </button>
                <img id="loadingSpinner" src="{{ asset('public/assets/spinner.gif') }}" class="w-8 hidden" alt="Loading">
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200" id="cardTable">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 border">File ID</th>
                        <th class="px-4 py-2 border">Action</th>
                        <th class="px-4 py-2 border">File No</th>
                        <th class="px-4 py-2 border">Lead ID</th>
                        <th class="px-4 py-2 border">Applicant Name</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- AJAX rows appear here -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-center" id="paginationLinks"></div>

    </div>
</div>


</x-app-layout>
