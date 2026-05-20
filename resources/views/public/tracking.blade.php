<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking: {{ $shipment->tracking_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4">Shipment: {{ $shipment->tracking_number }}</h1>
        <p><strong>Status:</strong> <span id="status">{{ $shipment->state }}</span></p>
        <p><strong>Destination:</strong> {{ $shipment->destination_address }}</p>

        <div class="mt-6">
            <h2 class="text-xl font-semibold mb-2">Live Map (Coordinates)</h2>
            <div id="map-placeholder" class="bg-gray-200 h-64 flex items-center justify-center rounded">
                <p id="coordinates" class="text-gray-600 font-mono">
                    @if($lat && $lng)
                        Lat: {{ $lat }}, Lng: {{ $lng }}
                    @else
                        Waiting for driver GPS...
                    @endif
                </p>
            </div>
        </div>
    </div>

    <script>
        window.reverbConfig = @json($reverbConfig);
        window.shipmentTenantId = {{ $shipment->tenant_id }};
    </script>
    
    <script type="module">
        import Echo from '/build/assets/app.js'; // This assumes standard Vite setup for Echo

        // Setup Echo
        if (window.Echo) {
            window.Echo.channel('tenant.' + window.shipmentTenantId + '.tracking')
                .listen('.location.updated', (e) => {
                    document.getElementById('coordinates').innerText = 'Lat: ' + e.lat + ', Lng: ' + e.lng;
                    console.log('Location updated:', e);
                });
        }
    </script>
</body>
</html>
