<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SalonService;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * Get all wishlist services.
     */
    public function getWishlist()
    {
        $serviceWishlist = SalonService::where('wishlist', 1)->get();
        return response()->json(['message' => 'Success', 'serviceWishlist' => $serviceWishlist]);
    }


    /**
     * Update the wishlist status of the specified service.
     */
    public function updateWishlist($serviceId)
    {
        $service = SalonService::findorFail($serviceId);
        if($service){
            if ($service->wishlist == 0) {
                $service->update(['wishlist' => 1]);
                return response()->json(['message' => 'Service added to wishlist']);
            } else {
                $service->update(['wishlist' => 0]);
                return response()->json(['message' => 'Service removed from wishlist']);
            }
        }else{
            return response()->json(['message' => 'Service not found']);
        }
    }
}