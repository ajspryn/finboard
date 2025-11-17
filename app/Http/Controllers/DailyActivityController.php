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
            $token = env('ABSENSI_API_TOKEN', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vYWJzZW5zaS50ZXN0L2FwaS9sb2dpbiIsImlhdCI6MTc2MzM1NTM5MSwiZXhwIjoxNzYzMzU4OTkxLCJuYmYiOjE3NjMzNTUzOTEsImp0aSI6InVMaERxUU1POWdBR0RlN0MiLCJzdWIiOiIxIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.GctVlE48LNh7RCj_0HgvDdGEuuV7b7vlrNK_NXzl4sE'); // Default token, ganti dengan token production

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

        // Filter berdasarkan tanggal (default hari ini)
        $filterDate = $request->get('date', now()->format('Y-m-d'));
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
