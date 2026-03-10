<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\Exercise;
use Illuminate\Http\Request;

class DateController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $filter = $request->input('filter', 'all');
        $userId = $request->user()->id;

        $result = [];

        if ($filter === 'all' || $filter === 'consumables') {
            $consumableDates = Consumable::query()
                ->whereHas('weightedProduct.product', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->where('record_date', 'like', "$month%")
                ->distinct()
                ->pluck('record_date');

            foreach ($consumableDates as $date) {
                // Ensure date string format Y-m-d
                $d = substr((string)$date, 0, 10);
                $result[$d][] = 'consumables';
            }
        }

        if ($filter === 'all' || $filter === 'exercises') {
            $exerciseDates = Exercise::query()
                ->where('user_id', $userId)
                ->where('record_date', 'like', "$month%")
                ->distinct()
                ->pluck('record_date');

            foreach ($exerciseDates as $date) {
                $d = substr((string)$date, 0, 10);
                if (!isset($result[$d])) {
                    $result[$d] = [];
                }
                if (!in_array('exercises', $result[$d])) {
                    $result[$d][] = 'exercises';
                }
            }
        }

        ksort($result);

        return response()->json($result);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'record_date' => 'required|date_format:Y-m-d',
        ]);

        $date = $validated['record_date'];
        $filter = $request->input('filter', 'all');
        $userId = $request->user()->id;

        if ($filter === 'all' || $filter === 'consumables') {
            Consumable::whereHas('weightedProduct.product', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->where('record_date', $date)
                ->delete();
        }

        if ($filter === 'all' || $filter === 'exercises') {
            Exercise::where('user_id', $userId)
                ->where('record_date', $date)
                ->delete();
        }

        return response()->noContent();
    }
}
