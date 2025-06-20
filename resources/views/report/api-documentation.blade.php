<x-app-layout title="{{ __('API Documentation') }}">
    <section class="content container-fluid">
        @if ($sMessage = Session::get('success'))
            <div class="alert alert-success auto-close">
                <p>{{ $sMessage }}</p>
            </div>
        @endif
        @if ($eMessage = Session::get('error'))
            <div class="alert alert-danger auto-close">
                <p>{{ $eMessage }}</p>
            </div>
        @endif

    <div class="row my-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white d-flex justify-content-between align-items-center bg-success">
                    <h5 class="mb-0"><i class="fas fa-tablet-alt me-2"></i>API Documentation</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <h2 class="mb-3">API Introduction</h2>
                        <p>The TanodTractor API provides programmatic access to tractor tracking and management functionality. All API requests require authentication via Bearer Token.</p>

                        <div class="alert alert-info">
                            <strong>Base URL:</strong> https://tanodtractor.com/api
                        </div>

                        <h4 class="mt-4">Authentication</h4>
                        <p>All API requests must include an Authorization header with a Bearer token obtained through Laravel Sanctum authentication.</p>
                        <pre class="bg-dark text-white p-3 rounded">Authorization: Bearer {your_access_token}</pre>
                    </div>

                    <div class="mb-4">
                        <div class="mb-5">


                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Login</h5>
                            </div>
                            <div class="card-body">
                                <pre><code class="language-http">POST /login</code></pre>
                                <h6>Request Body:</h6>
                                <pre><code class="language-json">{
    "email": "user@example.com",
    "password": "password",
    "device_name": "user_device"
}</code></pre>
                                <h6>Success Response:</h6>
                                <pre><code class="language-json">{
    "token": "1|randomtokenstring"
}</code></pre>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Logout</h5>
                            </div>
                            <div class="card-body">
                                <pre><code class="language-http">POST /logout</code></pre>
                                <h6>Headers:</h6>
                                <pre><code class="language-http">Authorization: Bearer {token}
Accept: application/json</code></pre>
                            </div>
                        </div>
                    </div>

                        <!-- User Management -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h3 class="h5 mb-0">User Management</h3>
                            </div>
                            <div class="card-body">
                                <h4>List Sub-Accounts</h4>
                                <pre class="bg-dark text-white p-3 rounded">GET /user/child/list?target={account}</pre>

                                <h4 class="mt-4">Create Sub-Account</h4>
                                <pre class="bg-dark text-white p-3 rounded">POST /user/child/create
                                    {
                                        "account_id": "string",
                                        "nick_name": "string",
                                        "account_type": 1,
                                        "password": "md5_hash",
                                        "email": "string",
                                        "permissions": "string",
                                        "telephone": "string", // optional
                                        "contact_person": "string", // optional
                                        "company_name": "string" // optional
                                    }</pre>

                                <h4 class="mt-4">Remove Sub-Account</h4>
                                <pre class="bg-dark text-white p-3 rounded">DELETE /user/child/del?account_id={accountId}</pre>
                            </div>
                        </div>

                        <!-- Device Management -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h3 class="h5 mb-0">Device Management</h3>
                            </div>
                            <div class="card-body">
                                <h4>Get Device List</h4>
                                <pre class="bg-dark text-white p-3 rounded">GET /user/device/list?target={account}</pre>

                                <h4 class="mt-4">Get Device Details</h4>
                                <pre class="bg-dark text-white p-3 rounded">GET /track/device/detail?imei={imei}</pre>

                                <h4 class="mt-4">Get Device Location</h4>
                                <pre class="bg-dark text-white p-3 rounded">GET /device/location/get?imeis=imei1,imei2,imei3</pre>

                                <h4 class="mt-4">Update Device Information</h4>
                                <pre class="bg-dark text-white p-3 rounded">PUT /open/device/update
{
    "imei": "string",
    "device_name": "string", // optional
    "vehicle_name": "string", // optional
    "vehicle_number": "string", // optional
    "driver_name": "string", // optional
    "driver_phone": "string", // optional
    "remarks": "string" // optional
}</pre>
                            </div>
                        </div>

                        <!-- Tracking -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h3 class="h5 mb-0">Tracking</h3>
                            </div>
                            <div class="card-body">
                                <h4>Get Device Track Data</h4>
                                <pre class="bg-dark text-white p-3 rounded">GET /device/track/list?imei={imei}&begin_time={YYYY-MM-DD HH:MM:SS}&end_time={YYYY-MM-DD HH:MM:SS}</pre>

                                <h4 class="mt-4">Get Device Mileage</h4>
                                <pre class="bg-dark text-white p-3 rounded">GET /device/track/mileage?imeis=imei1,imei2&begin_time={YYYY-MM-DD HH:MM:SS}&end_time={YYYY-MM-DD HH:MM:SS}</pre>
                            </div>
                        </div>

                        <!-- Geo-fence -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h3 class="h5 mb-0">Geo-fence</h3>
                            </div>
                            <div class="card-body">
                                <h4>Create Geo-fence</h4>
                                <pre class="bg-dark text-white p-3 rounded">POST /open/device/fence/create
{
    "imei": "string",
    "fence_name": "string",
    "lat": 0.0,
    "lng": 0.0,
    "radius": 100,
    "alarm_type": "out",
    "report_mode": "1",
    "alarm_switch": "ON"
}</pre>

                                <h4 class="mt-4">List Platform Geo-fences</h4>
                                <pre class="bg-dark text-white p-3 rounded">GET /open/platform/fence/list?account={account}&page_no=1&page_size=10</pre>
                            </div>
                        </div>

                        <!-- Reports -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h3 class="h5 mb-0">Reports</h3>
                            </div>
                            <div class="card-body">
                                <h4>Get Trips Report</h4>
                                <pre class="bg-dark text-white p-3 rounded">GET /open/platform/report/trips?account={account}&imeis=imei1,imei2&type={type}&start_time={YYYY-MM-DD HH:MM:SS}&end_time={YYYY-MM-DD HH:MM:SS}</pre>

                                <h4 class="mt-4">Get Parking/Idling Report</h4>
                                <pre class="bg-dark text-white p-3 rounded">GET /open/platform/report/parking?account={account}&imeis=imei1,imei2&start_time={YYYY-MM-DD HH:MM:SS}&end_time={YYYY-MM-DD HH:MM:SS}&acc_type={type}</pre>
                            </div>
                        </div>
                    </div>
                                        <div class="mb-5">
                        <h2 class="mb-3 border-bottom pb-2"><i class="fas fa-calendar-alt me-2"></i>Booking API</h2>

                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Create Booking</h5>
                            </div>
                            <div class="card-body">
                                <pre><code class="language-http">POST /bookings</code></pre>
                                <h6>Headers:</h6>
                                <pre><code class="language-http">Authorization: Bearer {token}
Content-Type: application/json</code></pre>
                                <h6>Request Body:</h6>
                                <pre><code class="language-json">{
    "tractor_id": 1,
    "start_date": "2023-06-01",
    "end_date": "2023-06-05",
    "purpose": "Farm work"
}</code></pre>
                                <h6>Success Response (201):</h6>
                                <pre><code class="language-json">{
    "id": 1,
    "user_id": 1,
    "tractor_id": 1,
    "status": "pending",
    "start_date": "2023-06-01"
}</code></pre>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Get User Bookings</h5>
                            </div>
                            <div class="card-body">
                                <pre><code class="language-http">GET /bookings</code></pre>
                                <h6>Success Response (200):</h6>
                                <pre><code class="language-json">[{
    "id": 1,
    "tractor_name": "John Deere",
    "start_date": "2023-06-01",
    "status": "approved"
}]</code></pre>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h2 class="mb-3">Response Codes</h2>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>200</td>
                                        <td>Success</td>
                                    </tr>
                                    <tr>
                                        <td>400</td>
                                        <td>Bad Request - Invalid parameters</td>
                                    </tr>
                                    <tr>
                                        <td>401</td>
                                        <td>Unauthorized - Invalid or expired token</td>
                                    </tr>
                                    <tr>
                                        <td>403</td>
                                        <td>Forbidden - Insufficient permissions</td>
                                    </tr>
                                    <tr>
                                        <td>500</td>
                                        <td>Internal Server Error</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <h4>Rate Limiting</h4>
                        <p>The API is rate limited to 60 requests per minute per authenticated user. Exceeding this limit will result in a 429 Too Many Requests response.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </section>
</x-app-layout>
