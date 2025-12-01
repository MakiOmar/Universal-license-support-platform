<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::query()->paginate(25);

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
}


