<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Models\Order;
use App\Jobs\UpdateProductStockJob;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Cache::remember('orders_admin', 900, function () {
            return Order::orderBy('created_at', 'desc')->get();
        });
        
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function store(Request $request)
    {
        // Debug: Log incoming request immediately
        \Log::info('=== ORDER STORE METHOD CALLED ===');
        \Log::info('Request method: ' . $request->method());
        \Log::info('Request headers: ' . json_encode($request->headers->all()));
        \Log::info('Request content type: ' . $request->header('Content-Type'));
        
        // Debug: Log incoming request data
        \Log::info('Order creation request data: ' . json_encode($request->all()));
        
        // Simplified validation to avoid timeout
        try {
            $validatedData = $request->validate([
                'user_info' => 'required|array',
                'user_info.fullName' => 'required|string|max:255',
                'user_info.email' => 'required|email|max:255',
                'user_info.phone' => 'required|string|max:20',
                'user_info.address' => 'required|string|max:500',
                'user_info.city' => 'required|string|max:100',
                'user_info.postalCode' => 'required|string|max:20',
                'user_info.country' => 'required|string|max:100',
                'items' => 'required|array',
                'items.*.id' => 'required|integer',
                'items.*.name' => 'required|string|max:255',
                'items.*.price' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.total' => 'required|string',
                'total_amount' => 'required|numeric|min:0',
                'status' => 'sometimes|string|in:pending,processing,shipped,delivered,cancelled',
                'payment_status' => 'sometimes|string|in:pending,paid,failed,refunded'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::info('Order validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            \Log::info('Order validated data: ' . json_encode($validatedData));
            
            // Create the order
            $order = Order::create($validatedData);
            \Log::info('Order created successfully with ID: ' . $order->id);

            // Dispatch stock update job to handle asynchronously
            if (isset($validatedData['items']) && is_array($validatedData['items'])) {
                \Log::info('Dispatching stock update job for ' . count($validatedData['items']) . ' items');
                
                UpdateProductStockJob::dispatch($validatedData['items']);
                
                \Log::info('Stock update job dispatched successfully');
            }

            // Clear caches
            Cache::forget('orders_admin');

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Order creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to place order: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $order = Cache::remember("order_{$id}", 3600, function () use ($id) {
            return Order::find($id);
        });
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'sometimes|string|in:pending,paid,failed,refunded',
            'notes' => 'sometimes|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order->update($request->only(['status', 'payment_status', 'notes']));

            // Clear order caches
            Cache::forget('orders_admin');
            Cache::forget("order_{$id}");

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        try {
            $order->delete();

            // Clear order caches
            Cache::forget('orders_admin');
            Cache::forget("order_{$id}");

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order: ' . $e->getMessage()
            ], 500);
        }
    }
}
