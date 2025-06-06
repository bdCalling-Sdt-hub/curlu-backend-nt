<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\Product;
use App\Models\ProductWishlist;
use App\Models\Salon;
use App\Models\SalonScheduleTime;
use App\Models\SalonService;
use App\Models\ServiceWishlist;
use App\Models\slider;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserServiceController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function homeSlider(Request $request)
    {
        // $sliders = slider::where('is_slider', 1)
        //             ->orderBy('created_at', 'desc')
        //             ->paginate($request->per_page ?? 10);
        $sliders = slider::paginate($request->per_page ?? 10);

        if ($sliders->isEmpty()) {
            return response()->json(['message' => 'No slider found']);
        }

        return response()->json(['message' => 'Success', 'sliders' => $sliders]);
    }

    public function populerService(Request $request)
    {

        $populerService = SalonService::with('category')
            ->where('service_status', 'active')
            ->where('popular', '>', 0)
            ->orderBy('popular', 'desc')
            ->paginate($request->per_page ?? 10);

        $populerService->transform(function ($service) {
            return [
                'service_id' => $service->id,
                'category_id' => $service->category_id,
                'category_name' => $service->category->name,
                'category_image' => $service->category->image,
                'salon_id' => $service->salon_id,
                'service_name' => $service->service_name,
                'price' => $service->price,
                'discount_price' => $service->discount_price,
                'service_image' => $service->service_image,
                'service_description' => $service->service_description,
                'popular' => $service->popular,
                'salon_name' => $service->salon->user->name . ' ' . $service->salon->user->last_name,
                'salon_address' => $service->salon->user->address,
                'salon_image' => $service->salon->user->image,
            ];
        });

        if ($populerService->isEmpty()) {
            return response()->json(['message' => 'No populer service found']);
        }
        return response()->json(['message' => 'Success', 'populerService' => $populerService]);
    }

    public function caregoryService(Request $request, $id)
    {
        $categoryService = SalonService::with('category')
            ->where('category_id', $id)
            ->where('service_status', 'active')
        // ->where('popular', '>', 0)
            ->orderBy('popular', 'desc')
            ->paginate($request->per_page ?? 10);

        if ($categoryService->isEmpty()) {
            return response()->json(['message' => 'No service found']);
        }
        return response()->json(['message' => 'Success', 'categoryService' => $categoryService]);
    }

    //get discount offers services
    public function serviceOffer(Request $request)
    {

        $offerService = SalonService::with('salon.user')
            ->whereNotNull('discount_price')
            ->orderBy('discount_price', 'desc')
            ->paginate($request->per_page ?? 10);

        $offerService->transform(function ($service) {

            $isInWishlist = ServiceWishlist::where('service_id', $service->id)
                ->where('user_id', Auth::user()->id)
                ->exists();

            return [
                'id' => $service->id,
                'service_id' => $service->id,
                'category_id' => $service->category_id,
                'salon_id' => $service->salon_id,
                'service_name' => $service->service_name,
                'price' => $service->price,
                'discount_price' => $service->discount_price,
                'service_image' => $service->service_image,
                'service_description' => $service->service_description,
                'service_status' => $service->service_status,
                'salon_name' => $service->salon->user->name . ' ' . $service->salon->user->last_name,
                'salon_address' => $service->salon->user->address,
                'salon_image' => $service->salon->user->image,
                'wishlist' => $isInWishlist,
            ];
        });

        if ($offerService->isEmpty()) {
            return response()->json(['message' => 'No offers found']);
        }

        return response()->json(['message' => 'Success', 'offerService' => $offerService]);
    }

    //get e-shop products
    public function eShopProduct(Request $request)
    {
        $userId = Auth::id();
        $products = Product::with('shop_category');
        if ($request->shop_category_id) {
            $products->where('shop_category_id', $request->shop_category_id);
        }
        if ($request->search) {
            $products->where('product_name', "LIKE", "%" . $request->search . "%");
        }
        $products = $products->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found']);
        }
        $wishlistProductIds = ProductWishlist::where('user_id', $userId)->pluck('product_id')->toArray();

        $products->getCollection()->transform(function ($product) use ($wishlistProductIds) {
            $product->in_wishlist = in_array($product->id, $wishlistProductIds);
            return $product;
        });
        return response()->json(['message' => 'Success', 'products' => $products]);
    }

    //get nearby professionals services
    public function getNearbyProfessionals(Request $request)
    {
        $user = auth()->user();

        $userLatitude = $request->latitude ?? $user->latitude;
        $userLongitude = $request->longitude ?? $user->longitude;

        if (empty($userLatitude) || empty($userLongitude)) {
            return response()->json(['message' => 'Please update your location to find nearby professionals']);
        }
        $radius = $request->radius ?? 10;

        $nearbyProfessionals = $this->userService->getNearbyProfessionals($userLatitude, $userLongitude, $radius);

        foreach ($nearbyProfessionals as $professional) {
            $professional->distance = $this->userService->distanceService->getDistance(
                $userLatitude,
                $userLongitude,
                $professional->latitude,
                $professional->longitude
            );
        }

        $nearbyProfessionals = collect($nearbyProfessionals)->transform(function ($professional) {
            $schedule = SalonScheduleTime::where('salon_id', $professional->salon->id)
                ->get()->transform(function ($item) {
                return is_string($item->schedule) ? json_decode($item->schedule, true) : $item->schedule;
            });
            $reviews = Feedback::where('salon_id', $professional->salon->id)
                ->avg('review');
            return [
                'user_id' => $professional->id,
                'salon_id' => $professional->salon->id,
                'name' => $professional->name,
                'last_name' => $professional->last_name,
                'address' => $professional->address,
                'distance' => $professional->distance,
                'image' => $professional->image,
                'cover_image' => $professional->cover_image,
                'salon_type' => $professional->salon->salon_type,
                'rating' => number_format($reviews, 1) ?? 0,
                'schedule_time' => $schedule,

            ];
        });

        if (empty($nearbyProfessionals)) {
            return response()->json(['message' => 'No nearby professionals found']);
        }

        return response()->json(['message' => 'Success', 'nearby_professionals' => $nearbyProfessionals]);
    }

    public function getNearbyProfessionalsByCategory(Request $request, $id)
    {
        $user = auth()->user();
        $userLatitude = $request->latitude ?? $user->latitude;
        $userLongitude = $request->longitude ?? $user->longitude;

        if (empty($userLatitude) || empty($userLongitude)) {
            return response()->json(['message' => 'Please update your location to find nearby professionals']);
        }
        $radius = $request->radius ?? 10;
        $perPage = $request->per_page ?? 10;
        $searchTerm = $request->search_term;

        $nearByServiceByCategory = $this->userService->getNearbyProfessionalsByCategory($userLatitude, $userLongitude, $radius, $id, $searchTerm, $perPage);

        if (empty($nearByServiceByCategory)) {
            return response()->json(['message' => 'No nearby professionals found']);
        }

        return response()->json(['message' => 'Success', 'nearbyProfessionalServices' => $nearByServiceByCategory]);
    }

    public function findServiceByProfessional(Request $request, $id)
    {

        try {
            $salon_user = Salon::with('user')->where('id', $id)->get();

            $salon_user = collect($salon_user)->map(function ($salon) use ($request) {
                $service = SalonService::where('salon_id', $salon->id)->paginate($request->per_page ?? 10);
                $schedule = SalonScheduleTime::where('salon_id', $salon->id)->get();
                $schedule = $schedule->map(function ($item) {
                    return is_string($item->schedule) ? json_decode($item->schedule, true) : $item->schedule;
                });
                return [
                    'salon_user' => [
                        'salon_id' => $salon->id,
                        'salon_user_id' => $salon->user->id,
                        'name' => $salon->user->name,
                        'last_name' => $salon->user->last_name,
                        'email' => $salon->user->email,
                        'phone' => $salon->user->phone,
                        'image' => $salon->user->image,
                        'cover_image' => $salon->user->cover_image,
                        'address' => $salon->user->address,
                        'salon_type' => $salon->salon_type,
                        'descriotion' => $salon->salon_description,
                        'experience' => $salon->experience,
                        'schedule_time' => $schedule,
                    ],
                    'services' => $service,
                ];
            });
            return response()->json([
                'message' => 'Success',
                'salon_services' => $salon_user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong ' . $e->getMessage()]);
        }
    }

}
