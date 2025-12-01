<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::query()->paginate(25);

        return response()->json($customers);
    }

    public function show(Customer $customer)
    {
        $customer->load(['licenses', 'supportTickets']);

        return response()->json($customer);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $customer = Customer::create($data);

        return response()->json($customer, 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'email' => ['sometimes', 'email', 'max:255', 'unique:customers,email,' . $customer->id],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $customer->update($data);

        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(null, 204);
    }
}


