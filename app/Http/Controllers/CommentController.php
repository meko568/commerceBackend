<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductComment;
use App\Models\Product;

class CommentController extends Controller
{
    /**
     * Store a new comment
     */
    public function store(Request $request, $productId)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'comment' => 'required|string|min:3|max:1000',
                'rating' => 'nullable|integer|min:1|max:5',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if product exists
            $product = Product::find($productId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Create comment
            $comment = ProductComment::create([
                'product_id' => $productId,
                'user_id' => Auth::id(),
                'comment' => $request->comment,
                'rating' => $request->rating,
                'is_approved' => true, // Auto-approve for now, can be changed to false for moderation
            ]);

            // Clear cache for this product's comments
            \Cache::forget("product_comments_{$productId}");

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'rating' => $comment->rating,
                    'user' => [
                        'name' => Auth::user()->name,
                        'email' => Auth::user()->email
                    ],
                    'created_at' => $comment->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comments for a product
     */
    public function index($productId)
    {
        try {
            $product = Product::find($productId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $comments = ProductComment::getApprovedComments($productId);

            return response()->json([
                'success' => true,
                'data' => $comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'rating' => $comment->rating,
                        'user' => [
                            'name' => $comment->user->name,
                            'email' => $comment->user->email
                        ],
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a comment (user can only delete their own)
     */
    public function destroy($commentId)
    {
        try {
            $comment = ProductComment::find($commentId);
            
            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            // Check if user owns the comment or is admin
            if ($comment->user_id !== Auth::id() && Auth::user()->email !== 'admin@gmail.com') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this comment'
                ], 403);
            }

            $comment->delete();

            // Clear cache for this product's comments
            \Cache::forget("product_comments_{$comment->product_id}");

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
