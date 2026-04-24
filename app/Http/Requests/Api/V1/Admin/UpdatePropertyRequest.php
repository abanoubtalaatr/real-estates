<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $propertyId = $this->route('property')?->id ?? $this->route('property');

        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'assigned_agent_id' => ['nullable', 'integer', 'exists:agents,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('properties', 'slug')->ignore($propertyId)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'listing_type' => ['sometimes', 'string', 'in:sale,rent'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:255'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:255'],
            'kitchens' => ['nullable', 'integer', 'min:0', 'max:255'],
            'status' => ['sometimes', 'string', 'in:draft,published'],
            'is_featured' => ['sometimes', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'max:5120'],
        ];
    }
}
