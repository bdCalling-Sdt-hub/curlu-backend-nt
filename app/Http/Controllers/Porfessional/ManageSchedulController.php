<?php

namespace App\Http\Controllers\Porfessional;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Salon;
use App\Models\SalonScheduleTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Stripe\Sigma\ScheduledQueryRun;

class ManageSchedulController extends Controller
{
    public function salonScheduleTime(Request $request)
    {
        $salonScheduleTime = SalonScheduleTime::where('salon_id', auth()->user()->salon->id)
            ->get();

        if ($salonScheduleTime->isEmpty()) {
            return response()->json(['message' => 'No salon schedule time found']);
        }

        $salonScheduleTime->transform(function ($scheduleTime) {
            $schedule = json_decode($scheduleTime->schedule);
            $scheduleTime->schedule = $schedule;
            return $scheduleTime;
        });

        return response()->json(['data' => $salonScheduleTime]);
    }

    public function storeSchedule(Request $request)
    {
        // $validated = Validator::make($request->all(), [
        //     'schedule' => 'required|json',
        //     'capacity' => 'required|integer',
        // ]);
        // if ($validated->fails()) {
        //     return response()->json(['message' => 'Validation failed', 'errors' => $validated->errors()]);
        // }
        $salon_id = auth()->user()->salon->id;
        $scheduleTime = SalonScheduleTime::where('salon_id',$salon_id)->first();
        if($scheduleTime){
            $scheduleTime->schedule = $request->schedule ?? $scheduleTime->schedule;
            $scheduleTime->salon_id = auth()->user()->salon->id;
            $scheduleTime->capacity = $request->capacity ?? $scheduleTime->capacity;
            $scheduleTime->save();

            return response()->json(['message' => 'Schedule time updated successfully']);
        }
        $scheduleTime = new SalonScheduleTime();
        $scheduleTime->schedule = $request->schedule;
        $scheduleTime->salon_id = $salon_id;
        $scheduleTime->capacity = $request->capacity;
        $scheduleTime->save();

        return response()->json(['message' => 'Schedule time added successfully']);
    }

    public function updateSchedule(Request $request, $id)
    {

        try {
            $updateSchedul = SalonScheduleTime::findOrfail($id);
            // dd($request->all());
            if ($updateSchedul) {
                $validated = Validator::make($request->all(), [
                    'schedule' => 'required|json',
                    'capacity' => 'required|integer',
                ]);

                if ($validated->fails()) {
                    return response()->json(['message' => 'Validation failed', 'errors' => $validated->errors()]);
                }

                $updateSchedul->schedule = $request->schedule;
                $updateSchedul->capacity = $request->capacity;
                $updateSchedul->save();

                return response()->json(['message' => 'Schedule time updated successfully', 'schedule' => $updateSchedul]);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong']);
        }
    }

    public function deleteSchedule($id)
    {

        try {
            $deleteSchedule = SalonScheduleTime::findOrfail($id);
            $deleteSchedule->delete();
            return response()->json(['message' => 'Schedule time deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong']);
        }
    }


    public function upcomingBooking(Request $request)
    {
        $upcomingBooking = Order::where('salon_id', auth()->user()->salon->id)
            ->Where('status', 'pending');
        // ->WhereDate('completed_at', '>', Carbon::parse($request->date))
        // ->when($request->date, function ($query) use ($request) {
        //     return $query->whereDate('completed_at', '=', $request->date);
        // })
        // ->orderBy('created_at', 'desc')
        // ->paginate($request->per_page ?? 10);

        if ($request->filled('date')) {
            $upcomingBooking->where('completed_at', '=', $request->date);
        }

        return $upcomingBooking->get();


        if ($upcomingBooking->isEmpty()) {
            return response()->json(['message' => 'No upcoming booking found']);
        }

        $upcomingBooking->getCollection()->transform(function ($booking) {

            return [
                'order_id' => $booking->id,
                'user_id' => $booking->user_id,
                'user_name' => $booking->user->name . ' ' . $booking->user->last_name,
                'salon_id' => $booking->service->salon->id,
                'salon_name' => $booking->service->salon->user->name . ' ' . $booking->service->salon->user->last_name,
                'order_number' => $booking->order_number,
                'total_amount' => $booking->total_amount,
                'status' => $booking->status == 'pending' ? 'Upcoming' : 'Processing',
                'booking_time' => Carbon::parse($booking->completed_at)->format('d M y h:i a'),
                'services' => [
                    'service_id' => $booking->service->id,
                    'service_name' => $booking->service->service_name,
                    'service_image' => $booking->service->image,
                ],
            ];
        });

        return response()->json(['message' => 'Success', 'upcomingBooking' => $upcomingBooking]);
    }
}
