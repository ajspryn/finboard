<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DailyActivityController extends Controller
{
    public function index(Request $request)
    {
        try {
            // API endpoint dan token
            $apiUrl = 'https://absensi.bprsbtb.co.id/api/daily-activities';
            $token = env('ABSENSI_API_TOKEN');

            // Request ke API
            $response = Http::withToken($token)->get($apiUrl);

            if ($response->successful()) {
                $apiData = $response->json();
                Log::info('Full API response', ['response' => $apiData]);
                $allActivities = $apiData['data'] ?? $apiData;
                Log::info('Extracted activities', ['activities' => $allActivities]);
            } else {
                Log::error('Failed to fetch daily activities', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                $allActivities = [];
            }
        } catch (\Exception $e) {
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
            $latestDate = collect($allActivities)->max(function ($activity) {
                return \Carbon\Carbon::parse($activity['date'])->format('Y-m-d');
            });
            $filterDate = $latestDate ?: now()->format('Y-m-d');
        } elseif (empty($filterDate)) {
            $filterDate = now()->format('Y-m-d');
        }
        $filterStatus = $request->get('status', '');
        $filterEmployee = $request->get('employee', '');

        $activities = collect($allActivities)->filter(function ($activity) use ($filterDate, $filterStatus, $filterEmployee) {
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
            'filterDate'
        ));
    }
}
