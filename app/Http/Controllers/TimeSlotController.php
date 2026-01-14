<?php

namespace App\Http\Controllers;

use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimeSlotController extends Controller
{
    public function index()
    {
        $timeSlots = TimeSlot::where('is_active', true)
                             ->orderBy('start_time')
                             ->get();

        return response()->json($timeSlots);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for overlapping time slots
        $overlapping = TimeSlot::where(function ($query) use ($request) {
            $query->where(function ($q) use ($request) {
                $q->where('start_time', '<=', $request->start_time)
                  ->where('end_time', '>', $request->start_time);
            })->orWhere(function ($q) use ($request) {
                $q->where('start_time', '<', $request->end_time)
                  ->where('end_time', '>=', $request->end_time);
            })->orWhere(function ($q) use ($request) {
                $q->where('start_time', '>=', $request->start_time)
                  ->where('end_time', '<=', $request->end_time);
            });
        })->exists();

        if ($overlapping) {
            return response()->json([
                'error' => 'Time slot overlaps with an existing time slot'
            ], 422);
        }

        $timeSlot = TimeSlot::create([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_active' => $request->get('is_active', true),
        ]);

        return response()->json($timeSlot, 201);
    }

    public function show($id)
    {
        $timeSlot = TimeSlot::findOrFail($id);

        return response()->json($timeSlot);
    }

    public function update(Request $request, $id)
    {
        $timeSlot = TimeSlot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for overlapping time slots (excluding current)
        if ($request->has('start_time') || $request->has('end_time')) {
            $startTime = $request->get('start_time', $timeSlot->start_time);
            $endTime = $request->get('end_time', $timeSlot->end_time);

            $overlapping = TimeSlot::where('id', '!=', $id)
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>', $startTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<', $endTime)
                          ->where('end_time', '>=', $endTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '>=', $startTime)
                          ->where('end_time', '<=', $endTime);
                    });
                })->exists();

            if ($overlapping) {
                return response()->json([
                    'error' => 'Time slot overlaps with an existing time slot'
                ], 422);
            }
        }

        $timeSlot->update($request->only(['start_time', 'end_time', 'is_active']));

        return response()->json($timeSlot);
    }

    public function destroy($id)
    {
        $timeSlot = TimeSlot::findOrFail($id);
        
        // Check if time slot has bookings
        if ($timeSlot->bookings()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete time slot with existing bookings'
            ], 422);
        }

        $timeSlot->delete();

        return response()->json(['message' => 'Time slot deleted successfully']);
    }

    public function bulkCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_hour' => 'required|integer|min:0|max:23',
            'end_hour' => 'required|integer|min:0|max:23|gt:start_hour',
            'interval' => 'required|integer|min:1|max:240', // minutes
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startHour = $request->start_hour;
        $endHour = $request->end_hour;
        $interval = $request->interval;

        $created = [];
        $skipped = [];

        $currentHour = $startHour;
        $currentMinute = 0;

        while ($currentHour < $endHour || ($currentHour === $endHour && $currentMinute === 0)) {
            $startTime = sprintf('%02d:00:00', $currentHour);
            
            // Calculate end time
            $totalMinutes = ($currentHour * 60) + $currentMinute + $interval;
            $endHourCalc = floor($totalMinutes / 60);
            $endMinuteCalc = $totalMinutes % 60;
            
            if ($endHourCalc > $endHour) {
                break;
            }
            
            $endTime = sprintf('%02d:%02d:00', $endHourCalc, $endMinuteCalc);

            // Check if already exists
            $exists = TimeSlot::where('start_time', $startTime)
                             ->where('end_time', $endTime)
                             ->exists();

            if (!$exists) {
                $timeSlot = TimeSlot::create([
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_active' => true,
                ]);
                $created[] = $timeSlot;
            } else {
                $skipped[] = ['start' => $startTime, 'end' => $endTime];
            }

            // Move to next interval
            $currentMinute += $interval;
            if ($currentMinute >= 60) {
                $currentHour += floor($currentMinute / 60);
                $currentMinute = $currentMinute % 60;
            } else {
                $currentHour += floor($currentMinute / 60);
            }
        }

        return response()->json([
            'created' => $created,
            'skipped' => $skipped,
            'message' => count($created) . ' time slots created, ' . count($skipped) . ' skipped'
        ], 201);
    }
}
