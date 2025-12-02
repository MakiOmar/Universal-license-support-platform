<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Jobs\ImportCustomersJob;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 25), 100);

        $customers = Customer::select('id', 'email', 'first_name', 'last_name', 'company', 'phone', 'status', 'created_at', 'updated_at')
            ->paginate($perPage);

        return CustomerResource::collection($customers);
    }

    public function show(Customer $customer)
    {
        $customer->load(['licenses', 'supportTickets']);

        return new CustomerResource($customer);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $customer = Customer::create($data);

        return new CustomerResource($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'email' => ['sometimes', 'email', 'max:255', 'unique:customers,email,' . $customer->id],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $customer->update($data);

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(null, 204);
    }

    public function getLicenses(Customer $customer)
    {
        $licenses = $customer->licenses()
            ->with(['product', 'activations'])
            ->paginate(25);

        return \App\Http\Resources\Api\V1\LicenseResource::collection($licenses);
    }

    public function getTickets(Customer $customer)
    {
        $tickets = $customer->supportTickets()
            ->with(['license', 'product', 'replies'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return \App\Http\Resources\Api\V1\SupportTicketResource::collection($tickets);
    }

    /**
     * Export customers to CSV
     */
    public function export(Request $request)
    {
        $customers = Customer::select('id', 'email', 'first_name', 'last_name', 'company', 'phone', 'status', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'customers_export_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');

            // Write CSV headers
            fputcsv($file, [
                'Email',
                'First Name',
                'Last Name',
                'Company',
                'Phone',
                'Status',
                'Created At',
                'Updated At',
            ]);

            // Write customer data
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->email,
                    $customer->first_name ?? '',
                    $customer->last_name ?? '',
                    $customer->company ?? '',
                    $customer->phone ?? '',
                    $customer->status,
                    $customer->created_at?->toIso8601String() ?? '',
                    $customer->updated_at?->toIso8601String() ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import customers from CSV file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('file');
        $userId = $request->user()->id;

        // Store file temporarily
        $filePath = $file->storeAs('imports', 'customers_' . time() . '_' . $file->getClientOriginalName(), 'local');

        // Dispatch import job
        ImportCustomersJob::dispatch($filePath, $userId);

        return response()->json([
            'message' => 'Import job queued successfully. The import will be processed in the background.',
            'status' => 'queued',
        ], 202);
    }

    /**
     * Check import status
     */
    public function importStatus(Request $request)
    {
        // For simplicity, we'll check the most recent import result
        // In production, you might want to track job IDs in a database
        $userId = $request->user()->id;

        // Try to find a recent import result in cache
        // This is a simplified approach - in production, you'd track job IDs
        $result = null;
        $keys = cache()->get('customer_import_keys_' . $userId, []);

        if (! empty($keys)) {
            foreach (array_reverse($keys) as $key) {
                $result = cache()->get($key);
                if ($result) {
                    break;
                }
            }
        }

        if (! $result) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No import results found',
            ], 404);
        }

        return response()->json([
            'status' => 'completed',
            'result' => $result,
        ]);
    }
}


