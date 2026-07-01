<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserRepository $userRepo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->userRepo->newQuery();

        if ($request->filled('role')) {
            $roles = explode(',', $request->input('role'));
            $query->whereIn('role', $roles);
        }

        $users = $query->select('id', 'email', 'role', 'is_active')
            ->orderBy('email')
            ->get();

        return response()->json([
            'data' => $users,
        ]);
    }
}
