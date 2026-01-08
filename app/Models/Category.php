<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get all child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all products in this category
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Check if this is a top-level category (no parent)
     */
    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this is a leaf category (no children)
     * Products must be attached to leaf categories only
     */
    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    /**
     * Get the depth of this category in the tree
     * Depth is calculated dynamically by counting parent chain
     */
    public function getDepth(): int
    {
        $depth = 0;
        $category = $this;
        
        while ($category->parent) {
            $depth++;
            $category = $category->parent;
        }
        
        return $depth;
    }

    /**
     * Get all ancestors (parent chain) of this category
     */
    public function getAncestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $category = $this->parent;
        
        while ($category) {
            $ancestors->push($category);
            $category = $category->parent;
        }
        
        return $ancestors->reverse();
    }

    /**
     * Get all descendants (children and their children) recursively
     */
    public function getDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }
        
        return $descendants;
    }

    /**
     * Get the full path of this category (e.g., "Eyewear > Men > Aviator")
     */
    public function getFullPath(string $separator = ' > '): string
    {
        $path = collect([$this->name]);
        $category = $this->parent;
        
        while ($category) {
            $path->prepend($category->name);
            $category = $category->parent;
        }
        
        return $path->implode($separator);
    }
}
