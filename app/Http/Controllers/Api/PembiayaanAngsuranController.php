<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembiayaan;
use Illuminate\Http\Request;

class PembiayaanAngsuranController extends Controller
{
     public function index(Request $request)
     {
          $validated = $request->validate([
               'month' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])$/'],
               'year' => ['required', 'string', 'regex:/^\d{4}$/'],
               'segmentasi' => ['nullable', 'string', 'max:50'],
               'q' => ['nullable', 'string', 'max:100'],
               'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
          ]);

          $month = $validated['month'];
          $year = $validated['year'];
          $segmentasi = isset($validated['segmentasi']) && trim($validated['segmentasi']) !== ''
               ? trim($validated['segmentasi'])
               : null;
          $search = isset($validated['q']) && trim($validated['q']) !== ''
               ? trim($validated['q'])
               : null;

          $query = Pembiayaan::query()
               ->where('period_month', $month)
               ->where('period_year', $year);

          if ($segmentasi !== null) {
               $query->where('kdgroupdeb', $segmentasi);
          }

          if ($search !== null) {
               $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
               $query->where(function ($sub) use ($like) {
                    $sub->where('nama', 'like', $like)
                         ->orWhere('nokontrak', 'like', $like);
               });
          }

          $perPage = (int) ($validated['per_page'] ?? 50);

          $paginator = $query
               ->select([
                    'id',
                    'nokontrak',
                    'nama',
                    'angsmdl',
                    'angsmgn',
                    'period_month',
                    'period_year',
                    'kdgroupdeb',
               ])
               ->selectRaw('(COALESCE(angsmdl, 0) + COALESCE(angsmgn, 0)) as angsuran_total')
               ->orderBy('nama')
               ->paginate($perPage);

          $items = $paginator->getCollection()->map(function ($row) {
               return [
                    'id' => $row->id,
                    'no_rek' => $row->nokontrak,
                    'nama' => $row->nama,
                    'segmentasi' => $row->kdgroupdeb,
                    'period_month' => $row->period_month,
                    'period_year' => $row->period_year,
                    'angsuran_pokok' => $row->angsmdl,
                    'angsuran_margin' => $row->angsmgn,
                    'angsuran_total' => $row->angsuran_total,
               ];
          });

          return response()->json([
               'filters' => [
                    'month' => $month,
                    'year' => $year,
                    'segmentasi' => $segmentasi,
                    'q' => $search,
               ],
               'data' => $items,
               'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
               ],
          ]);
     }
}
