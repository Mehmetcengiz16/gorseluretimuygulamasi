<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditTransactionResource;
use App\Http\Resources\GenerationImageResource;
use App\Http\Resources\UserResource;
use App\Models\GenerationImage;
use App\Services\ImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MeController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(Request $request, ImageStorage $storage): UserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'avatar' => ['sometimes', 'image', 'max:5120'],
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $storage->storeUpload($request->file('avatar'), 'avatars/'.$user->id, 'avatar')['path'];
        }
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        $user->save();

        return new UserResource($user);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Hesabın silindi.']);
    }

    public function creditTransactions(Request $request): AnonymousResourceCollection
    {
        return CreditTransactionResource::collection(
            $request->user()->creditTransactions()->latest('id')->paginate(30)
        );
    }

    public function favorites(Request $request): AnonymousResourceCollection
    {
        $images = GenerationImage::query()
            ->where('is_favorite', true)
            ->whereHas('generation', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('updated_at')
            ->paginate(30);

        return GenerationImageResource::collection($images);
    }
}
