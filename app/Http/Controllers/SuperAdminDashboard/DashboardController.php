<?php
namespace App\Http\Controllers\SuperAdminDashboard;

use App\Http\Controllers\Controller;
use App\Models\PlatformFee;
use App\Models\Salon;
use App\Models\SalonInvoice;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // count
        $total_user    = User::where('role_type', 'USER')->count();
        $daily_user    = User::where('role_type', 'USER')->whereDate('created_at', now())->count();
        $total_salon   = Salon::count();
        $total_earning = SalonInvoice::sum('curlu_earning');
        // app user
        $appUsers = User::query()
            ->selectRaw('MONTH(created_at) as month')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        $app_users = [];
        for ($i = 1; $i <= 12; $i++) {
            $app_users[] = [
                'month' => $i,
                'user'  => $appUsers->firstWhere('month', $i)->count ?? 0,
            ];
        }

        // active user
        $activeUsers = User::query()
            ->where('role_type', 'USER')
            ->where('user_status', 'active')
            ->selectRaw('MONTH(created_at) as month')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        $active_users = [];
        for ($i = 1; $i <= 12; $i++) {
            $active_users[] = [
                'month' => $i,
                'user'  => $activeUsers->firstWhere('month', $i)->count ?? 0,
            ];
        }

        // earning growth
        $year = $request->filled('year') ? $request->input('year') : date('Y');

        $earnings = SalonInvoice::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, SUM(curlu_earning) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthlyEarnings = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyEarnings[] = [
                'month' => $i,
                'total' => $earnings->firstWhere('month', $i)->total ?? 0,
            ];
        }
        //  salon statistics
        $salons = Salon::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthlySalons = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlySalons[] = [
                'month' => $i,
                'count' => $salons->firstWhere('month', $i)->count ?? 0,
            ];
        }

        return response()->json([
            'total_user'            => $total_user,
            'daily_user'            => $daily_user,
            'total_salon'           => $total_salon,
            'total_earning'         => $total_earning,
            'app_users'             => $app_users,
            'active_users'          => $active_users,
            'total_earning_growth'  => $monthlyEarnings,
            'total_salon_statistic' => $monthlySalons,
        ], 200);
    }

    public function updatePlatformFee(Request $request)
    {
        $platform_fee                = PlatformFee::findOrFail(1);
        $platform_fee->curlu_earning = $request->curlu_earning;
        $platform_fee->save();
        return response()->json([
            'status'  => 'true',
            'message' => 'Platform fee updated successfully.',
            'data'    => $platform_fee,
        ]);
    }
    public function getPlatformFee()
    {
        $platform_fee                = PlatformFee::findOrFail(1);
        return response()->json([
            'status'  => 'true',
            'message' => 'Platform fee retreived successfully.',
            'data'    => $platform_fee,
        ]);
    }
}
