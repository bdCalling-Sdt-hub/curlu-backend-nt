<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Salon;
use App\Models\Review;
use App\Models\SalonInvoice;
use App\Models\SalonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\OrderConfirmNotification;

class OrderHistory extends Controller
{
    public function orderHistory(Request $request)
    {
        $per_page       = $request->per_page ?? 10;
        $salon_invoices = SalonInvoice::with('salon.user:id,name,last_name,address', 'service', 'user:id,name,last_name,address');
        if (Auth::user()->role_type == 'PROFESSIONAL') {
            $salon          = Salon::where('user_id', Auth::user()->id)->first();
            $salon_invoices = $salon_invoices->where('salon_id', $salon->id);
        }
        $salon_invoices = $salon_invoices->latest('id')->paginate($per_page);
        foreach ($salon_invoices as $invoice) {
            $invoice->review = Review::where('salon_invoice_id', $invoice->id)
                ->where('user_id', $invoice->user_id)
                ->first();
        }
        return response()->json([
            'status'  => true,
            'message' => 'History retrieve successfully.',
            'data'    => $salon_invoices,
        ]);
    }

    public function orderHistorysingle($id)
    {
        $salon_invoice         = SalonInvoice::with('salon.user:id,name,last_name,address', 'service', 'user:id,name,last_name,address')->where('id', $id)->first();
        $salon_invoice->review = Review::where('salon_invoice_id', $salon_invoice->id)
            ->where('user_id', $salon_invoice->user_id)
            ->first();
        return response()->json([
            'status'  => true,
            'message' => 'Data retrieve successfully.',
            'data'    => $salon_invoice,
        ]);
    }

    public function qrScan($id)
    {
        $salon_invoice         = SalonInvoice::with('user')->where('invoice_number', $id)->first();
        $salon_invoice->status = 'Past';
        $salon_invoice->save();

        $order=Order::where('invoice_number',$id)->first();
        $order->status='completed';
        $order->save();


        $salon=Salon::findOrFail($salon_invoice->salon_id);
        $service_name      = SalonService::where('id', $salon_invoice->service_id)->first();
        $salon_details=User::find($salon->user_id);
        $notification_data = [
            'service_name' => $service_name->service_name,
            'salon_name'   => $salon_details->name . ' ' . $salon_details->last_name,
        ];
        $user = User::findOrFail($salon_invoice->user_id);
        if ($user) {
            $user->notify(new OrderConfirmNotification($notification_data));
        }

        return response()->json([
            'status'  => true,
            'message' => 'Data retrieve successfully.',
            'data'    => $salon_invoice,
        ]);
    }
}
