<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\CcPointSetting;
use Illuminate\Support\Str; 
class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category', 'images')->latest()->paginate(10);
        $categories = ProductCategory::all();
        $ccSetting = CcPointSetting::getCurrent(); // ✅ Load CC setting

        return view('admin.pages.products', compact('products', 'categories', 'ccSetting'));
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'category_id' => 'required|exists:product_categories,id',
        'name' => 'required|string|max:255',
        'sku' => 'required|string|unique:products,sku',
        'short_description' => 'nullable|string|max:500',
        'description' => 'nullable|string',
        'uses' => 'nullable|string',
        'directions_for_use' => 'nullable|string',
        'cautions' => 'nullable|string',
        'primary_benefits' => 'nullable|string',
        'ingredients' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'discount_price' => 'nullable|numeric|min:0|lt:price',
        'cc_points' => 'required|numeric|min:0',
        'size' => 'nullable|string|max:100',
        'brand' => 'nullable|string|max:100',
        'stock' => 'required|integer|min:0',
        'status' => 'required|in:0,1',
        'featured' => 'boolean',
        'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    // ✅ Auto-generate slug from product name
    $validated['slug'] = Str::slug($validated['name']);
    
    // ✅ Ensure slug is unique (add number if duplicate)
    $slug = $validated['slug'];
    $count = 1;
    while (Product::where('slug', $validated['slug'])->exists()) {
        $validated['slug'] = $slug . '-' . $count;
        $count++;
    }

    $validated['in_stock'] = $validated['stock'] > 0;
    $validated['featured'] = $request->has('featured');
    $validated['status'] = $request->status;

    if (empty($validated['cc_points'])) {
        $price = $validated['discount_price'] ?? $validated['price'];
        $validated['cc_points'] = CcPointSetting::calculateCCFromPrice($price);
    }

    $product = Product::create($validated);

    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $position => $image) {
            $path = $image->store('products', 'public');
            $product->images()->create([
                'image_path' => $path,
                'position' => $position,
            ]);
        }
    }

    return redirect()->route('products.index')
        ->with('success', 'Product created successfully!');
}

   public function update(Request $request, Product $product)
{
    // Check if ONLY CC points is being updated
    if ($request->has('cc_points') && !$request->has(['name', 'sku', 'price', 'stock'])) {
        $validated = $request->validate([
            'cc_points' => 'required|numeric|min:0',
        ]);
        
        $product->update([
            'cc_points' => (int) $validated['cc_points'],
        ]);
        
        return redirect()->back()
            ->with('success', 'CC Points updated successfully!');
    }
    
    // Full product update
    $validated = $request->validate([
        'category_id' => 'required|exists:product_categories,id',
        'name' => 'required|string|max:255',
        'sku' => 'required|string|unique:products,sku,' . $product->id,
        'short_description' => 'nullable|string|max:500',
        'description' => 'nullable|string',
        
        // ✅ Added missing fields
        'uses' => 'nullable|string',
        'directions_for_use' => 'nullable|string',
        'cautions' => 'nullable|string',
        'primary_benefits' => 'nullable|string',
        'ingredients' => 'nullable|string',
        
        'price' => 'required|numeric|min:0',
        'discount_price' => 'nullable|numeric|min:0|lt:price',
        'cc_points' => 'required|numeric|min:0',
        'size' => 'nullable|string|max:100',
        'brand' => 'nullable|string|max:100',
        'stock' => 'required|integer|min:0',
        'status' => 'required|in:0,1',
        'featured' => 'boolean',
        'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'remove_images' => 'nullable|array',
        'remove_images.*' => 'integer',
    ]);
    
    // Auto-generate slug if name changed
    if ($request->has('name') && $request->name !== $product->name) {
        $validated['slug'] = Str::slug($validated['name']);
        $slug = $validated['slug'];
        $count = 1;
        while (Product::where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
            $validated['slug'] = $slug . '-' . $count;
            $count++;
        }
    }
    
    if ($request->has('remove_images') && is_array($request->remove_images)) {
        foreach ($request->remove_images as $imageId) {
            $productImage = ProductImage::find($imageId);
            if ($productImage) {
                Storage::disk('public')->delete($productImage->image_path);
                $productImage->delete();
            }
        }
    }

    if ($request->hasFile('images')) {
        $maxPosition = $product->images()->max('position') ?? -1;
        foreach ($request->file('images') as $position => $image) {
            $path = $image->store('products', 'public');
            $product->images()->create([
                'image_path' => $path,
                'position' => $maxPosition + 1 + $position,
            ]);
        }
    }
    
    $validated['in_stock'] = $validated['stock'] > 0;
    $validated['featured'] = $request->has('featured');
    $validated['status'] = (int) $request->status;
    
    // Auto-calculate CC points if not provided
    if (empty($validated['cc_points'])) {
        $price = $validated['discount_price'] ?? $validated['price'];
        $validated['cc_points'] = CcPointSetting::calculateCCFromPrice($price);
    } else {
        $validated['cc_points'] = (int) $validated['cc_points'];
    }

    $product->update($validated);

    return redirect()->route('products.index')
        ->with('success', 'Product updated successfully!');
}

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
{
    // ✅ Check if product is used in any order
    $hasOrders = \App\Models\OrderItem::where('product_id', $product->id)->exists();
    
    if ($hasOrders) {
        // ❌ Cannot delete - mark as inactive instead
        $product->update([
            'status' => 0,  // Set to inactive
            'stock' => 0,   // Set stock to 0
        ]);
        
        return redirect()->route('products.index')
            ->with('warning', "Product '{$product->name}' cannot be deleted as it has order history. It has been marked as <strong>Inactive</strong> instead.");
    }
    
    // ✅ Safe to delete - no orders reference this product
    foreach ($product->images as $productImage) {
        Storage::disk('public')->delete($productImage->image_path);
        $productImage->delete();
    }
    
    $product->delete();
    
    return redirect()->route('products.index')
        ->with('success', 'Product deleted successfully!');
}
    /**
     * Remove specific image from product.
     */
    public function removeImage(Request $request, Product $product)
    {
        $imageId = $request->image_id;
        $productImage = ProductImage::find($imageId);

        if ($productImage && $productImage->product_id === $product->id) {
            Storage::disk('public')->delete($productImage->image_path);
            $productImage->delete();
        }

        return response()->json(['success' => true]);
    }
    public function updateCC(Request $request, Product $product)
    {
        $validated = $request->validate([
            'cc_points' => 'required|integer|min:0',
        ]);

        $product->update([
            'cc_points' => $validated['cc_points'],
        ]);

        return redirect()->back()
            ->with('success', 'CC Points updated successfully!');
    }
}
