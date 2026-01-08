<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminBannerController extends Controller
{
    /**
     * List all banners
     */
    public function index(Request $request)
    {
        $query = Banner::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $banners = $query->orderBy('position')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($banners, 'Banners retrieved successfully');
    }

    /**
     * Create a new banner
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required_without:image_url|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_url' => 'required_without:image|string|url',
            'link_type' => 'required|in:category,product,external',
            'link_id' => 'nullable|integer',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
        ], [
            'title.required' => 'Banner title is required.',
            'title.max' => 'Banner title cannot exceed 255 characters.',
            'image.required_without' => 'Either image file or image URL is required.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Image must be one of: jpeg, png, jpg, gif, webp.',
            'image.max' => 'Image size cannot exceed 5MB.',
            'image_url.required_without' => 'Either image file or image URL is required.',
            'image_url.url' => 'Please provide a valid image URL.',
            'link_type.required' => 'Link type is required.',
            'link_type.in' => 'Link type must be one of: category, product, external.',
            'end_at.after' => 'End date must be after start date.',
        ]);

        $imagePath = null;

        // Handle file upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('banners', $filename, 'public');
            $imagePath = Storage::url($path);
        } 
        // Handle URL
        elseif ($request->has('image_url')) {
            $imagePath = $request->image_url;
        }

        // Get next position if not provided
        $position = $request->position ?? Banner::max('position') + 1 ?? 0;

        $banner = Banner::create([
            'title' => $request->title,
            'image_url' => $imagePath,
            'link_type' => $request->link_type,
            'link_id' => $request->link_id,
            'position' => $position,
            'is_active' => $request->boolean('is_active', true),
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
        ]);

        return $this->success($banner, 'Banner created successfully', 201);
    }

    /**
     * Delete a banner
     */
    public function destroy(Banner $banner)
    {
        // Delete file from storage if it's a local file
        if (strpos($banner->image_url, '/storage/') === 0) {
            $filePath = str_replace('/storage/', '', $banner->image_url);
            Storage::disk('public')->delete($filePath);
        }

        $banner->delete();

        return $this->success(null, 'Banner deleted successfully');
    }
}
