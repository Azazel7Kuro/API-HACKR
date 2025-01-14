<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogController extends Controller
{
    public function getLogs(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        // verification admin
        if (!$user->roles->contains('name', 'admin')) {
            return response()->json(['error' => 'Access denied. Admins only.'], 403);
        }

        $logs = Log::all();
        return response()->json($logs);
    }
    /**
     * @OA\Get(
     *     path="/api/logs/{id_action}",
     *     tags={"Logs"},
     *     summary="Retrieve logs by action ID",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id_action",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logs retrieved successfully",
     *         @OA\JsonContent(type="array",
     *             @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="action", type="string"),
     *                 @OA\Property(property="date", type="string", format="date-time"),
     *                 @OA\Property(property="user_name", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No logs found for this action ID",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No logs found")
     *         )
     *     )
     * )
     */
    public function getLogsByFunctionId($id_action): \Illuminate\Http\JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        // verification admin
        if (!$user->roles->contains('name', 'admin')) {
            return response()->json(['error' => 'Access denied. Admins only.'], 403);
        }

        $logs = Log::with('user')->where('id_action', $id_action)->get();

        $formattedLogs = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'date' => $log->date,
                'user_name' => $log->user->name,
            ];
        });

        return response()->json($formattedLogs);
    }

    /**
     * @OA\Get(
     *     path="/api/user-logs/{id_user}",
     *     tags={"Logs"},
     *     summary="Retrieve all logs for a specific user",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id_user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logs retrieved successfully",
     *         @OA\JsonContent(type="array",
     *             @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="action", type="string"),
     *                 @OA\Property(property="date", type="string", format="date-time"),
     *                 @OA\Property(property="user_name", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No logs found for this user",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No logs found for this user")
     *         )
     *     )
     * )
     */

    public function getUserLogs($id_user): \Illuminate\Http\JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        // verification admin
        if (!$user->roles->contains('name', 'admin')) {
            return response()->json(['error' => 'Access denied. Admins only.'], 403);
        }

        $logs = Log::with('user')->where('id_user', $id_user)->get();

        $formattedLogs = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'date' => $log->date,
                'user_name' => $log->user->name,
            ];
        });

        return response()->json($formattedLogs);
    }
}

