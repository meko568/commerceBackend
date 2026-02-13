<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Cache::forget('products_active');

        $products = Cache::remember('products_active', 3600, function () {
            return Product::where('is_active', true)->orderBy('created_at', 'desc')->get();
        });

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
                    'main_image' => $product->main_image,
                    'additional_images' => $product->additional_images ?? [],
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
            'main_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'additional_images' => 'nullable|array',
            'additional_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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
            // Upload main image to Cloudinary
            $mainImageUrl = Cloudinary::upload(
                $request->file('main_image')->getRealPath(),
                ['folder' => 'products']
            )->getSecurePath();

            // Upload additional images to Cloudinary
            $additionalImages = [];
            if ($request->hasFile('additional_images')) {
                foreach ($request->file('additional_images') as $image) {
                    $additionalImages[] = Cloudinary::upload(
                        $image->getRealPath(),
                        ['folder' => 'products']
                    )->getSecurePath();
                }
            }

            $product = Product::create([
                'name' => $request->name,
                'short_description' => $request->short_description,
                'long_description' => $request->long_description,
                'price' => $request->price,
                'sale_price' => $request->sale_price,
                'stock' => $request->stock,
                'main_image' => $mainImageUrl,
                'additional_images' => $additionalImages,
                'is_active' => $request->boolean('is_active', true)
            ]);

            Cache::forget('products_active');

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'short_description' => $product->short_description,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'stock' => $product->stock,
                    'main_image' => $product->main_image,
                    'additional_images' => $product->additional_images ?? [],
                    'is_active' => $product->is_active,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ]
            ], 201);

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

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'short_description' => $product->short_description,
                'long_description' => $product->long_description,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'current_price' => $hasSale ? $product->sale_price : $product->price,
                'has_sale' => $hasSale,
                'stock' => $product->stock,
                'main_image' => $product->main_image,
                'additional_images' => $product->additional_images ?? [],
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
            'main_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'additional_images' => 'sometimes|nullable|array',
            'additional_images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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
            $updateData = $request->only([
                'name', 'short_description', 'long_description',
                'price', 'sale_price', 'stock', 'is_active'
            ]);

            // Handle main image update
            if ($request->hasFile('main_image')) {
                // Delete old image from Cloudinary
                if ($product->main_image) {
                    $publicId = $this->getCloudinaryPublicId($product->main_image);
                    if ($publicId) Cloudinary::destroy($publicId);
                }

                $updateData['main_image'] = Cloudinary::upload(
                    $request->file('main_image')->getRealPath(),
                    ['folder' => 'products']
                )->getSecurePath();
            }

            // Handle additional images update
            if ($request->hasFile('additional_images')) {
                // Delete old additional images from Cloudinary
                if ($product->additional_images) {
                    foreach ($product->additional_images as $oldImage) {
                        $publicId = $this->getCloudinaryPublicId($oldImage);
                        if ($publicId) Cloudinary::destroy($publicId);
                    }
                }

                $additionalImages = [];
                foreach ($request->file('additional_images') as $image) {
                    $additionalImages[] = Cloudinary::upload(
                        $image->getRealPath(),
                        ['folder' => 'products']
                    )->getSecurePath();
                }
                $updateData['additional_images'] = $additionalImages;
            }

            $product->update($updateData);

            Cache::forget('products_active');
            Cache::forget("product_{$id}");

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'short_description' => $product->short_description,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'stock' => $product->stock,
                    'main_image' => $product->main_image,
                    'additional_images' => $product->additional_images ?? [],
                    'is_active' => $product->is_active,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ]
            ], 200);

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
            // Delete main image from Cloudinary
            if ($product->main_image) {
                $publicId = $this->getCloudinaryPublicId($product->main_image);
                if ($publicId) Cloudinary::destroy($publicId);
            }

            // Delete additional images from Cloudinary
            if ($product->additional_images) {
                foreach ($product->additional_images as $image) {
                    $publicId = $this->getCloudinaryPublicId($image);
                    if ($publicId) Cloudinary::destroy($publicId);
                }
            }

            $product->delete();

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

    /**
     * Extract Cloudinary public ID from a secure URL.
     * e.g. https://res.cloudinary.com/demo/image/upload/v123/products/abc.jpg
     * returns: products/abc
     */
    private function getCloudinaryPublicId(string $url): ?string
    {
        try {
            $path = parse_url($url, PHP_URL_PATH);
            // Remove /image/upload/vXXXXXX/ prefix
            $path = preg_replace('/\/image\/upload\/v\d+\//', '/', $path);
            // Remove file extension
            $publicId = preg_replace('/\.[^.]+$/', '', ltrim($path, '/'));
            return $publicId ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}