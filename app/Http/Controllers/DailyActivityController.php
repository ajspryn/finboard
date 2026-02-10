<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class DailyActivityController extends Controller
{
    public function index(Request $request)
    {
        $apiConnected = false;
        $apiStatusCode = null;
        $apiError = null;

        try {
            // API endpoint dan token
            $apiUrl = config('services.absensi.url', 'https://absensi.bprsbtb.co.id/api/daily-activities');
            $token = config('services.absensi.token', env('ABSENSI_API_TOKEN'));

            if (!$token) {
                $apiError = 'ABSENSI_API_TOKEN belum diset di environment.';
                $allActivities = [];
            } else {
                // Request ke API
                $response = Http::timeout(15)
                    ->retry(2, 200)
                    ->acceptJson()
                    ->withToken($token)
                    ->get($apiUrl);

                $apiStatusCode = $response->status();

                if ($response->successful()) {
                    $apiConnected = true;
                    $apiData = $response->json();
                    Log::info('Full API response', ['response' => $apiData]);

                    // Normalize payload to a list of activities.
                    $candidate = data_get($apiData, 'data.data');
                    if (!is_array($candidate)) {
                        $candidate = data_get($apiData, 'data');
                    }
                    if (!is_array($candidate)) {
                        $candidate = $apiData;
                    }

                    if (is_array($candidate) && !Arr::isList($candidate)) {
                        // If still associative, try common key names.
                        $candidate = data_get($candidate, 'data', []);
                    }

                    $allActivities = (is_array($candidate) && Arr::isList($candidate)) ? $candidate : [];
                    Log::info('Extracted activities', ['count' => count($allActivities)]);
                } else {
                    $apiError = $response->json('message') ?? $response->reason() ?? 'Failed to fetch daily activities';
                    Log::error('Failed to fetch daily activities', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    $allActivities = [];
                }
            }
        } catch (\Exception $e) {
            $apiError = $e->getMessage();
            Log::error('Exception while fetching daily activities', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $allActivities = [];
        }

        // Filter berdasarkan tanggal (default tanggal terakhir yang ada data)
        $filterDate = $request->get('date', '');

        // Jika tidak ada filter tanggal, gunakan tanggal terakhir yang ada data
        if (empty($filterDate) && !empty($allActivities)) {
            $latestDate = collect($allActivities)
                ->filter(fn($activity) => is_array($activity) && !empty($activity['date']))
                ->max(function ($activity) {
                    return \Carbon\Carbon::parse($activity['date'])->format('Y-m-d');
                });
            $filterDate = $latestDate ?: now()->format('Y-m-d');
        } elseif (empty($filterDate)) {
            $filterDate = now()->format('Y-m-d');
        }
        $filterStatus = $request->get('status', '');
        $filterEmployee = $request->get('employee', '');

        $activities = collect($allActivities)->filter(function ($activity) use ($filterDate, $filterStatus, $filterEmployee) {
            if (!is_array($activity) || empty($activity['date'])) {
                return false;
            }
            // Filter berdasarkan tanggal
            $activityDate = \Carbon\Carbon::parse($activity['date'])->format('Y-m-d');
            if ($activityDate !== $filterDate) {
                return false;
            }

            // Filter berdasarkan status
            if ($filterStatus && $activity['status'] !== $filterStatus) {
                return false;
            }

            // Filter berdasarkan karyawan
            if ($filterEmployee && (!isset($activity['employee']['full_name']) || stripos($activity['employee']['full_name'], $filterEmployee) === false)) {
                return false;
            }

            return true;
        })->values()->all();

        // Data untuk filter dropdown
        $availableStatuses = collect($allActivities)->pluck('status')->unique()->values()->all();
        $availableEmployees = collect($allActivities)->pluck('employee.full_name')->unique()->filter()->values()->all();

        return view('daily-activity.index', compact(
            'activities',
            'allActivities',
            'filterDate',
            'apiConnected',
            'apiStatusCode',
            'apiError'
        ));
    }
}
