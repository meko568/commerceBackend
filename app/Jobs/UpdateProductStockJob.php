<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UpdateProductStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $items
    ) {
        $this->items = $items;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting stock update job for ' . count($this->items) . ' items');
        
        foreach ($this->items as $item) {
            try {
                $productId = $item['id'];
                $quantity = $item['quantity'];
                
                Log::info("Processing stock update for product {$productId}, quantity: {$quantity}");
                
                $product = Product::find($productId);
                
                if (!$product) {
                    Log::warning("Product {$productId} not found, skipping stock update");
                    continue;
                }
                
                if ($product->stock >= $quantity) {
                    // Update stock
                    $newStock = $product->stock - $quantity;
                    $product->stock = $newStock;
                    $product->save();
                    
                    // Clear product cache
                    Cache::forget('products_active');
                    Cache::forget("product_{$productId}");
                    
                    Log::info("Stock updated for product {$productId}: -{$quantity} units (new stock: {$newStock})");
                } else {
                    Log::warning("Insufficient stock for product {$productId}: available={$product->stock}, requested={$quantity}");
                }
                
            } catch (\Exception $e) {
                Log::error("Error updating stock for product {$item['id']}: " . $e->getMessage());
                // Continue with other items even if one fails
            }
        }
        
        Log::info('Stock update job completed');
    }
}
