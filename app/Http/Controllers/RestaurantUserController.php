<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\User;

class RestaurantUserController extends Controller
{
    public function attachUser(Request $r, Restaurant $restaurant) {
        $this->authorizeManager($r->user(), $restaurant);
        $data = $r->validate([
            'user_id'=>'required|exists:users,id',
            'role'=>'required|in:owner,manager,waiter'
        ]);
        $restaurant->users()->syncWithoutDetaching([$data['user_id']=>['role'=>$data['role']]]);
        return ['ok'=>true];
    }
    public function detachUser(Request $r, Restaurant $restaurant, User $user) {
        $this->authorizeOwner($r->user(), $restaurant);
        $restaurant->users()->detach($user->id);
        return ['ok'=>true];
    }
    protected function role($user, Restaurant $r) {
        return $r->users()->where('users.id',$user->id)->first()?->pivot?->role;
    }
    protected function authorizeManager($u, Restaurant $r) { abort_if(!in_array($this->role($u,$r),['owner','manager']),403); }
    protected function authorizeOwner($u, Restaurant $r) { abort_if($this->role($u,$r)!=='owner',403); }
}
