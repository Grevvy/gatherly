<?php

namespace App\Http\Requests;

use App\Models\Community;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * @property Community $community
 */
class UpdateCommunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // In Laravel, when using route model binding, the model is available as a property
        $community = $this->community;
        
        // Check if user is owner or admin
        return $community->memberships()
            ->where('user_id', Auth::id())
            ->whereIn('role', ['owner', 'admin'])
            ->where('status', 'active')
            ->exists();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string'],
            'banner_image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'visibility' => ['sometimes', 'in:public,private'],
            'join_policy' => ['sometimes', 'in:open,request,invite'],
        ];
    }
}