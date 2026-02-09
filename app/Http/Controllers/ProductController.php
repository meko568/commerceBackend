<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Debug: Log the query
        \Log::info('Fetching products...');
        
        // Clear cache for debugging
        Cache::forget('products_active');
        
        $products = Cache::remember('products_active', 3600, function () {
            \Log::info('Cache miss, fetching from database...');
            $allProducts = Product::all();
            \Log::info('Total products in DB: ' . $allProducts->count());
            
            $activeProducts = Product::where('is_active', true)->orderBy('created_at', 'desc')->get();
            \Log::info('Active products count: ' . $activeProducts->count());
            
            // Log each product's status
            foreach ($allProducts as $product) {
                \Log::info('Product: ' . $product->name . ', is_active: ' . $product->is_active);
            }
            
            return $activeProducts;
        });

        \Log::info('Final products count: ' . $products->count());

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($product) {
                $hasSale = !is_null($product->sale_price) && $product->sale_price < $product->price;
                $currentPrice = $hasSale ? $product->sale_price : $product->price;
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'short_description' => $product->short_description,
                    'long_description' => $product->long_description,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'current_price' => $currentPrice,
                    'has_sale' => $hasSale,
                    'stock' => $product->stock,
                    'main_image' => $product->main_image_url,
                    'additional_images' => $product->additional_images_urls,
                    'is_active' => $product->is_active,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ];
            })
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'short_description' => 'required|string|max:500',
            'long_description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'main_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'additional_images' => 'nullable|array',
            'additional_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Debug: Log request data
            \Log::info('Product creation request:', [
                'all_data' => $request->all(),
                'has_main_image' => $request->hasFile('main_image'),
                'has_additional_images' => $request->hasFile('additional_images'),
                'additional_images_count' => $request->hasFile('additional_images') ? count($request->file('additional_images')) : 0
            ]);

            // Handle main image upload
            $mainImagePath = $request->file('main_image')->store('products', 'public');

            // Handle additional images
            $additionalImages = [];
            if ($request->hasFile('additional_images')) {
                foreach ($request->file('additional_images') as $image) {
                    $additionalImages[] = $image->store('products', 'public');
                }
            }

            $product = Product::create([
                'name' => $request->name,
                'short_description' => $request->short_description,
                'long_description' => $request->long_description,
                'price' => $request->price,
                'sale_price' => $request->sale_price,
                'stock' => $request->stock,
                'main_image' => $mainImagePath,
                'additional_images' => $additionalImages,
                'is_active' => $request->boolean('is_active', true)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'short_description' => $product->short_description,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'current_price' => $product->current_price,
                    'has_sale' => $product->has_sale,
                    'stock' => $product->stock,
                    'main_image' => $product->main_image_url,
                    'additional_images' => $product->additional_images_url,
                    'is_active' => $product->is_active,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ]
            ], 201);

            // Clear product caches
            Cache::forget('products_active');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Cache::remember("product_{$id}", 3600, function () use ($id) {
            return Product::find($id);
        });
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $hasSale = !is_null($product->sale_price) && $product->sale_price < $product->price;
        $currentPrice = $hasSale ? $product->sale_price : $product->price;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'short_description' => $product->short_description,
                'long_description' => $product->long_description,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'current_price' => $currentPrice,
                'has_sale' => $hasSale,
                'stock' => $product->stock,
                'main_image' => $product->main_image_url,
                'additional_images' => $product->additional_images_urls,
                'is_active' => $product->is_active,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'short_description' => 'sometimes|required|string|max:500',
            'long_description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'sometimes|nullable|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'main_image' => 'sometimes|required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'additional_images.*' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Debug image handling
            \Log::info('Request method: ' . $request->method());
            \Log::info('Request content type: ' . $request->header('Content-Type'));
            \Log::info('Request has main image: ' . ($request->hasFile('main_image') ? 'Yes' : 'No'));
            \Log::info('Request all files: ' . json_encode($request->allFiles()));
            \Log::info('Request all input: ' . json_encode($request->all()));
            
            $updateData = $request->only([
                'name', 'short_description', 'long_description', 
                'price', 'sale_price', 'stock', 'is_active'
            ]);

            // Handle main image update
            if ($request->hasFile('main_image')) {
                \Log::info('Processing main image update');
                // Delete old main image
                if ($product->main_image) {
                    Storage::disk('public')->delete($product->main_image);
                }
                $updateData['main_image'] = $request->file('main_image')->store('products', 'public');
                \Log::info('New main image stored: ' . $updateData['main_image']);
            } else {
                \Log::info('No main image in request');
            }

            // Handle additional images update
            if ($request->hasFile('additional_images')) {
                \Log::info('Processing additional images update');
                // Delete old additional images
                if ($product->additional_images) {
                    foreach ($product->additional_images as $oldImage) {
                        Storage::disk('public')->delete($oldImage);
                    }
                }

                $additionalImages = [];
                foreach ($request->file('additional_images') as $image) {
                    $additionalImages[] = $image->store('products', 'public');
                }
                $updateData['additional_images'] = $additionalImages;
                \Log::info('New additional images stored: ' . json_encode($additionalImages));
            } else {
                \Log::info('No additional images in request');
            }

            $product->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'short_description' => $product->short_description,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'current_price' => $product->current_price,
                    'has_sale' => $product->has_sale,
                    'stock' => $product->stock,
                    'main_image' => $product->main_image_url,
                    'additional_images' => $product->additional_images_url,
                    'is_active' => $product->is_active,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ]
            ], 200);

            // Clear product caches
            Cache::forget('products_active');
            Cache::forget("product_{$id}");

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        try {
            // Delete main image
            if ($product->main_image) {
                Storage::disk('public')->delete($product->main_image);
            }

            // Delete additional images
            if ($product->additional_images) {
                foreach ($product->additional_images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }

            $product->delete();

            // Clear product caches
            Cache::forget('products_active');
            Cache::forget("product_{$id}");

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
