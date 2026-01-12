<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Controllers\Vendor\ProductVariantController;
use App\Http\Controllers\Vendor\ProductImageController;
use App\Http\Controllers\Vendor\VendorDashboardController;
use App\Http\Controllers\Vendor\VendorOrderController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminVendorController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminBannerController;
use App\Http\Controllers\Admin\AdminFeaturedProductController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\ImageUploadController;

Route::get('/ping', function () {
    return response()->json(['status' => 'ok']);
});

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

// Public Category Routes (no authentication required)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
});

Route::middleware(['auth:sanctum', 'role:vendor'])
    ->get('/test-vendor', fn () => ['ok' => true]);


// Checkout Route (Customer only)
Route::middleware(['auth:sanctum', 'role:customer'])
    ->post('/checkout', [CheckoutController::class, 'checkout']);
// Vendor Product Management Routes
Route::middleware(['auth:sanctum', 'role:vendor', 'vendor.scope'])
    ->prefix('vendor')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [VendorDashboardController::class, 'index']);

        // Products
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Product Variants
        Route::get('/products/{product}/variants', [ProductVariantController::class, 'index']);
        Route::post('/products/{product}/variants', [ProductVariantController::class, 'store']);
        Route::put('/variants/{variant}', [ProductVariantController::class, 'update']);
        Route::delete('/variants/{variant}', [ProductVariantController::class, 'destroy']);

        // Product Images
        Route::get('/products/{product}/images', [ProductImageController::class, 'index']);
        Route::post('/products/{product}/images', [ProductImageController::class, 'store']);
        Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy']);

        // Orders
        Route::get('/orders', [VendorOrderController::class, 'index']);
        Route::get('/orders/{order}', [VendorOrderController::class, 'show']);
        Route::put('/orders/{order}/status', [VendorOrderController::class, 'updateStatus']);
    });

// Customer Routes
Route::middleware(['auth:sanctum', 'role:customer'])
    ->prefix('customer')
    ->group(function () {
        // Cart
        Route::get('/cart', [CartController::class, 'viewCart']);
        Route::post('/cart/add', [CartController::class, 'addToCart']);
        Route::put('/cart/items/{cartItem}', [CartController::class, 'updateCartItem']);
        Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeCartItem']);
        Route::delete('/cart', [CartController::class, 'clearCart']);

        // Checkout
        Route::post('/checkout', [CheckoutController::class, 'checkout']);

        // Orders
        Route::get('/orders', [CustomerOrderController::class, 'index']);
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show']);
    });

// Admin Routes
Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboardStats']);

        // Users
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']); // Create customer
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::post('/users/{user}/approve', [AdminUserController::class, 'approve']);
        Route::post('/users/{user}/reject', [AdminUserController::class, 'reject']);

        // Vendors
        Route::get('/vendors', [AdminVendorController::class, 'index']);
        Route::get('/vendors/{vendor}', [AdminVendorController::class, 'show']);
        Route::post('/vendors/{vendor}/approve', [AdminVendorController::class, 'approve']);
        Route::post('/vendors/{vendor}/reject', [AdminVendorController::class, 'reject']);

        // Products (full CRUD)
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::post('/products', [AdminProductController::class, 'store']);
        Route::get('/products/{product}', [AdminProductController::class, 'show']);
        Route::put('/products/{product}', [AdminProductController::class, 'update']);
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);

        // Orders (read-only)
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{order}', [AdminOrderController::class, 'show']);

        // Banners
        Route::get('/banners', [AdminBannerController::class, 'index']);
        Route::post('/banners', [AdminBannerController::class, 'store']);
        Route::delete('/banners/{banner}', [AdminBannerController::class, 'destroy']);

        // Featured Products
        Route::get('/featured-products', [AdminFeaturedProductController::class, 'index']);
        Route::post('/featured-products', [AdminFeaturedProductController::class, 'store']);
        Route::delete('/featured-products/{featuredProduct}', [AdminFeaturedProductController::class, 'destroy']);

        // Categories
        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::get('/categories/{category}', [AdminCategoryController::class, 'show']);
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);
    });

Route::middleware('auth:sanctum')->post(
    '/upload-image',
    [ImageUploadController::class, 'upload']
);
